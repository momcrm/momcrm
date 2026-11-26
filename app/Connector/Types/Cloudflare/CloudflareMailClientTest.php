<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Cloudflare\CloudflareAccount;
use App\Connector\Types\Cloudflare\DeliveredMail;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Actions\SyncMailbox;
use App\Mailbox\Mailbox;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\OutgoingMail;

use function Pest\Laravel\travel;
use function Pest\Laravel\travelTo;

/**
 * The account a mailbox's connector is of.
 */
function cloudflareAccountOf(Mailbox $mailbox): string
{
    return ConnectorEloquentModel::query()->withoutGlobalScopes()->findOrFail($mailbox->getConnectorId()->toString())->account;
}

/**
 * A reply to a customer, answering an email when one is given.
 */
function replyThroughCloudflare(?MailboxMessage $answered = null): OutgoingMail
{
    return new OutgoingMail(['sam@acme.test'], "We are on it.\nThanks", 'Printer offline', '[#12]', $answered?->toInboundMail());
}

describe('reading', function (): void {
    it('lists the mail handed over since a time, oldest first, once it has been left to arrive', function (): void {
        Storage::fake();
        travelTo('2026-11-18 01:00:00');
        $delivered = DeliveredMail::forAddress('support@helpdesk.test');
        $early = $delivered->keep(emailSource(['Message-ID' => '<early@acme.test>']));
        travelTo('2026-11-18 01:10:00');
        $first = $delivered->keep(emailSource(['Message-ID' => '<first@acme.test>']));
        travelTo('2026-11-18 01:12:00');
        $second = $delivered->keep(emailSource(['Message-ID' => '<second@acme.test>']));
        travelTo('2026-11-18 01:14:30');
        $delivered->keep(emailSource(['Message-ID' => '<just-arrived@acme.test>']));
        travelTo('2026-11-18 01:15:00');
        $client = ResolveMailClient::forMailbox(cloudflareMailbox());

        $listed = $client->inboxMessagesSince(now()->toImmutable()->setTime(1, 5), 50);

        expect(array_column($listed, 'id'))->toBe([$first, $second])
            ->and($listed[0]['received_at']->toDateTimeString())->toBe('2026-11-18 01:10:00')
            ->and(array_column($client->inboxMessagesSince(now()->toImmutable()->setTime(0, 0), 2), 'id'))->toBe([$early, $first]);
    });

    it('reads an email that was handed over, with its sender, its thread and when it arrived', function (): void {
        Storage::fake();
        travelTo('2026-11-18 01:15:00');
        $id = DeliveredMail::forAddress('support@helpdesk.test')->keep(emailSource([
            'In-Reply-To' => '<sent-1@helpdesk.test>',
            'References' => '<first@acme.test> <sent-1@helpdesk.test>',
        ], "Still offline.\r\n\r\nOn Tue, 17 Nov 2026 at 09:00, Support <support@helpdesk.test> wrote:\r\n> Is it back?", withFile: true));

        expect(ResolveMailClient::forMailbox(cloudflareMailbox())->message($id))
            ->externalId->toBe($id)
            ->messageId->toBe('message-1@acme.test')
            ->inReplyTo->toBe('sent-1@helpdesk.test')
            ->references->toBe(['first@acme.test', 'sent-1@helpdesk.test'])
            ->fromAddress->toBe('sam@acme.test')
            ->fromName->toBe('Sam Lee')
            ->to->toBe(['support@helpdesk.test'])
            ->subject->toBe('Printer offline')
            ->replyText->toBe('Still offline.')
            ->hasAttachments->toBeTrue()
            ->bodyHtml->toBeNull()
            ->and(ResolveMailClient::forMailbox(cloudflareMailbox(['address' => 'other@helpdesk.test']))->message($id))->toBeNull();
    });

    it('reads an email that came with HTML as its text and, apart from it, as its sender laid it out', function (): void {
        Storage::fake();
        $id = DeliveredMail::forAddress('support@helpdesk.test')->keep(implode("\r\n", [
            'From: Sam Lee <sam@acme.test>',
            'To: support@helpdesk.test',
            'Subject: Welcome',
            'Message-ID: <message-2@acme.test>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="parts"',
            '',
            '--parts',
            'Content-Type: text/plain; charset=utf-8',
            '',
            'Sign in https://acme.test/in',
            '--parts',
            'Content-Type: text/html; charset=utf-8',
            '',
            '<p><a href="https://acme.test/in">Sign in</a></p>',
            '--parts--',
            '',
        ]));

        expect(ResolveMailClient::forMailbox(cloudflareMailbox())->message($id))
            ->bodyText->toBe('Sign in https://acme.test/in')
            ->bodyHtml->toContain('<p><a href="https://acme.test/in">Sign in</a></p>');
    });

    it('opens a ticket with its file for mail the job that takes it in as it arrives missed', function (): void {
        Storage::fake();
        Http::preventStrayRequests();
        travelTo('2026-11-18 01:00:00');
        $mailbox = cloudflareMailbox(['created_at' => now(), 'synced_through' => now()]);
        travel(5)->minutes();
        DeliveredMail::forAddress('support@helpdesk.test')->keep(emailSource(withFile: true));
        travel(5)->minutes();

        expect(SyncMailbox::fromMailbox($mailbox))->toBe(1)
            ->and(SyncMailbox::fromMailbox($mailbox))->toBe(0);

        $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();
        $file = AttachmentEloquentModel::query()->withoutGlobalScopes()->sole();

        expect($ticket)
            ->title->toBe('Printer offline')
            ->requester_email->toBe('sam@acme.test')
            ->mailbox_id->toBe($mailbox->getId()->toString())
            ->and($file->name)->toBe('log.txt')
            ->and(Storage::get($file->path))->toBe('paper jam');
    });

    it('clears out mail kept for longer than a week as more arrives, and keeps the same email once', function (): void {
        Storage::fake();
        travelTo('2026-11-18 01:00:00');
        $delivered = DeliveredMail::forAddress('support@helpdesk.test');
        $old = $delivered->keep(emailSource(['Message-ID' => '<old@acme.test>']));
        travel(6)->days();
        $recent = $delivered->keep(emailSource());
        travel(2)->days();

        expect($delivered->keep(emailSource()))->toBe($recent)
            ->and($delivered->source($old))->toBeNull()
            ->and($delivered->source($recent))->toBe(emailSource())
            ->and($delivered->source('../../.env'))->toBeNull();
    });
});

describe('sending', function (): void {
    it('sends a reply through Email Sending under the email it answers, and keeps the Message-ID Cloudflare gave it', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        $account = cloudflareAccountOf($mailbox);
        $answered = MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create([
            'internet_message_id' => 'message-1@acme.test',
            'reference_ids' => ['first@acme.test'],
            'subject' => 'Printer offline',
        ]));
        Http::fake([
            CLOUDFLARE_API."/accounts/{$account}/email/sending/send" => Http::response(cloudflareResult([
                'message_id' => '<sent-9@helpdesk.test>',
                'delivered' => ['sam@acme.test'],
                'permanent_bounces' => [],
                'queued' => [],
            ])),
        ]);

        expect(ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare($answered)))
            ->externalId->toBeNull()
            ->internetMessageId->toBe('sent-9@helpdesk.test')
            ->subject->toBe('Re: Printer offline [#12]');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer cloudflare-token')
            && $request->data() === [
                'from' => 'support@helpdesk.test',
                'to' => ['sam@acme.test'],
                'subject' => 'Re: Printer offline [#12]',
                'text' => "We are on it.\nThanks",
                'html' => "We are on it.<br>\nThanks",
                'headers' => [
                    'In-Reply-To' => '<message-1@acme.test>',
                    'References' => '<first@acme.test> <message-1@acme.test>',
                ],
            ]);
    });

    it('starts a thread without naming an email to answer', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareResult(['message_id' => '<sent-9@helpdesk.test>', 'queued' => ['sam@acme.test']])),
        ]);

        expect(ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare())->subject)->toBe('Printer offline [#12]');

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('headers', $request->data()));
    });

    it('says what to put right in Cloudflare when it refuses to send', function (int $status, int $code, string $reason): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareRefusal($code, 'email.sending.error'), $status),
        ]);

        expect(fn () => ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare()))
            ->toThrow(CouldNotReachConnector::class, $reason);

        Http::assertSentCount(1);
        expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('connected');
    })->with([
        'sending is off for the domain' => [403, 10203, 'Email Sending is not turned on for this domain. Turn it on in Cloudflare, under Email Service, then send again.'],
        'the plan only sends to verified addresses' => [403, 10208, 'This Cloudflare plan only sends to addresses verified in the account. The Workers Paid plan sends to anyone.'],
        'the token may not send' => [403, 10102, 'The API token may not send email. Create one that has Email Sending (Edit), and connect Cloudflare again.'],
        'something else' => [400, 10202, 'Cloudflare refused the request: email.sending.error'],
    ]);

    it('does not send again when Cloudflare is having trouble', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        Http::fake([CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareRefusal(10002, 'email.sending.error.internal_server'), 500)]);

        try {
            ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare());
            $thrown = null;
        } catch (CouldNotReachConnector $exception) {
            $thrown = $exception;
        }

        expect($thrown?->isTemporary)->toBeTrue()
            ->and($thrown?->company)->toBe('Cloudflare');
        Http::assertSentCount(1);
    });

    it('fails the reply when Cloudflare could deliver it to nobody', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareResult(['message_id' => '<sent-9@helpdesk.test>', 'delivered' => [], 'permanent_bounces' => ['sam@acme.test'], 'queued' => []])),
        ]);

        expect(fn () => ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare()))
            ->toThrow(CouldNotReachConnector::class, 'Cloudflare could not deliver to sam@acme.test.');
    });

    it('asks for the account to be connected again when Cloudflare no longer accepts the token', function (): void {
        Http::preventStrayRequests();
        $mailbox = cloudflareMailbox();
        Http::fake([
            CLOUDFLARE_API.'/accounts/*/email/sending/send' => Http::response(cloudflareRefusal(10101, 'email.sending.error.authentication.unauthorized'), 401),
            CLOUDFLARE_API.'/*/tokens/verify' => Http::response(cloudflareRefusal(1000, 'Invalid API Token'), 401),
        ]);

        expect(fn () => ResolveMailClient::forMailbox($mailbox)->send(replyThroughCloudflare()))
            ->toThrow(CouldNotReachConnector::class, 'Cloudflare no longer accepts the sign-in for this connector. Connect it again.');

        expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole())
            ->status->toBe('needs_reconnect')
            ->last_error->toBe('Cloudflare no longer accepts the sign-in for this connector. Connect it again.');
    });
});

it('can read a mailbox only while Cloudflare hands its mail to this installation\'s Worker', function (?string $worker, bool $opens): void {
    Http::preventStrayRequests();
    $mailbox = cloudflareMailbox();
    $ours = CloudflareAccount::workerName(cloudflareAccountOf($mailbox));
    Http::fake([
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
        CLOUDFLARE_API.'/zones/zone-1/email/routing/rules*' => Http::response(cloudflareResult($worker === null ? [] : [[
            'id' => 'rule-1',
            'matchers' => [['type' => 'literal', 'field' => 'to', 'value' => 'Support@Helpdesk.test']],
            'actions' => [['type' => 'worker', 'value' => [$worker === 'ours' ? $ours : $worker]]],
        ]])),
    ]);

    $check = fn () => ResolveMailClient::forMailbox($mailbox)->checkInbox();

    $opens
        ? expect($check)->not->toThrow(CouldNotReachConnector::class)
        : expect($check)->toThrow(CouldNotReachConnector::class, "Cloudflare is not handing support@helpdesk.test's mail to MomCRM. Remove the mailbox and add it again.");
})->with([
    'the rule hands mail to our Worker' => ['ours', true],
    'the rule hands mail to another Worker' => ['someone-elses-worker', false],
    'the address has no rule' => [null, false],
]);

it('names what Cloudflare refused when a working API token lacks a permission', function (): void {
    Http::preventStrayRequests();
    $mailbox = cloudflareMailbox();
    Http::fake([
        CLOUDFLARE_API.'/zones?*' => Http::response(cloudflareResult([['id' => 'zone-1', 'name' => 'helpdesk.test']])),
        CLOUDFLARE_API.'/zones/zone-1/email/routing/rules*' => Http::response(cloudflareRefusal(10000, 'Authentication error'), 403),
        CLOUDFLARE_API.'/user/tokens/verify' => Http::response(cloudflareResult(['status' => 'active'])),
    ]);

    expect(fn () => ResolveMailClient::forMailbox($mailbox)->checkInbox())
        ->toThrow(CouldNotReachConnector::class, "Cloudflare did not let MomCRM list the domain's routing rules. Check that the API token has every permission MomCRM asks for, and that it covers this domain.");

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('connected');
});
