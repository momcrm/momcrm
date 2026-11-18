<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Actions\SyncMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\OutgoingMail;

use function Pest\Laravel\travelTo;

const GMAIL = 'gmail.googleapis.com/gmail/v1/users/me';

beforeEach(function (): void {
    Http::preventStrayRequests();
});

/**
 * A mailbox that goes through a Google connector with a token that is still good.
 */
function googleMailbox(): Mailbox
{
    $connector = ConnectorEloquentModel::factory()->google()->create(['account' => 'help@helpdesk.test']);
    $mailbox = MailboxEloquentModel::factory()->create([
        'team_id' => $connector->team_id,
        'connector_id' => $connector->id,
        'address' => 'help@helpdesk.test',
    ]);
    rememberMailboxToken($mailbox);

    return Mailbox::fromEloquentModel($mailbox);
}

function googleMailClient(): MailClient
{
    return ResolveMailClient::forMailbox(googleMailbox());
}

/**
 * Text as Gmail hands it over: URL-safe base64.
 */
function gmailData(string $text): string
{
    return rtrim(strtr(base64_encode($text), '+/', '-_'), '=');
}

/**
 * A text part of a Gmail message.
 *
 * @return array<string, mixed>
 */
function gmailPart(string $partId, string $mimeType, string $text, string $charset = 'UTF-8'): array
{
    return [
        'partId' => $partId,
        'mimeType' => $mimeType,
        'filename' => '',
        'headers' => [['name' => 'Content-Type', 'value' => "{$mimeType}; charset=\"{$charset}\""]],
        'body' => ['size' => strlen($text), 'data' => gmailData($text)],
    ];
}

/**
 * An email as the Gmail API returns it in full.
 *
 * @param  array<string, mixed>  $overrides
 * @param  array<string, string>  $headers
 * @param  list<array<string, mixed>>|null  $parts
 * @return array<string, mixed>
 */
function gmailMessage(array $overrides = [], array $headers = [], ?array $parts = null): array
{
    $headers = [
        'From' => 'Kim Lee <Kim@Acme.test>',
        'To' => 'Help Desk <help@helpdesk.test>',
        'Subject' => 'Printer offline',
        'Message-ID' => '<msg-1@acme.test>',
        ...$headers,
    ];

    return [
        'id' => 'gmail-1',
        'threadId' => 'thread-1',
        'labelIds' => ['INBOX', 'UNREAD'],
        'internalDate' => (string) CarbonImmutable::parse('2026-11-18 01:00:00', 'UTC')->getTimestampMs(),
        'payload' => [
            'partId' => '',
            'mimeType' => 'multipart/alternative',
            'filename' => '',
            'headers' => array_map(fn (string $name, string $value): array => ['name' => $name, 'value' => $value], array_keys($headers), $headers),
            'body' => ['size' => 0],
            'parts' => $parts ?? [
                gmailPart('0', 'text/plain', "The printer on level 2 is offline.\n"),
                gmailPart('1', 'text/html', '<p>The printer on level 2 is offline.</p>'),
            ],
        ],
        ...$overrides,
    ];
}

/**
 * The decoded message a request asked Gmail to send.
 */
function gmailSentRaw(Request $request): string
{
    return (string) base64_decode(strtr((string) $request['raw'], '-_', '+/'), true);
}

it('opens the inbox to check the mailbox can be read', function (): void {
    Http::fake([GMAIL.'/labels/INBOX' => Http::response(['id' => 'INBOX'])]);

    googleMailClient()->checkInbox();

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer access-token'));
});

it('lists the oldest inbox messages since a time, oldest first, and fetches each once', function (): void {
    $since = CarbonImmutable::parse('2026-11-18 00:00:00', 'UTC');
    $at = fn (string $time): string => (string) CarbonImmutable::parse("2026-11-18 {$time}", 'UTC')->getTimestampMs();

    Http::fake(fn (Request $request) => match (true) {
        str_contains($request->url(), '/messages?') && str_contains($request->url(), 'pageToken=page-2') => Http::response(['messages' => [['id' => 'gmail-2'], ['id' => 'gmail-1'], ['id' => 'gmail-0']]]),
        str_contains($request->url(), '/messages?') => Http::response(['messages' => [['id' => 'gmail-4'], ['id' => 'gmail-3']], 'nextPageToken' => 'page-2']),
        str_contains($request->url(), '/messages/gmail-0?') => Http::response(gmailMessage(['id' => 'gmail-0', 'internalDate' => (string) $since->subSecond()->getTimestampMs()])),
        str_contains($request->url(), '/messages/gmail-1?') => Http::response(gmailMessage(['id' => 'gmail-1', 'internalDate' => $at('01:00:00')])),
        str_contains($request->url(), '/messages/gmail-2?') => Http::response(gmailMessage(['id' => 'gmail-2', 'internalDate' => $at('02:00:00')])),
        default => Http::response(['error' => ['message' => 'unexpected']], 400),
    });

    $client = googleMailClient();
    $messages = $client->inboxMessagesSince($since, 3);

    expect(array_column($messages, 'id'))->toBe(['gmail-1', 'gmail-2'])
        ->and($messages[0]['received_at']->toIso8601String())->toBe('2026-11-18T01:00:00+00:00')
        ->and($client->message('gmail-2')?->externalId)->toBe('gmail-2');

    Http::assertSentCount(5);
    Http::assertSent(function (Request $request) use ($since): bool {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_contains($request->url(), '/messages?')
            && $query['labelIds'] === 'INBOX'
            && $query['q'] === 'after:'.$since->getTimestamp()
            && ! isset($query['pageToken']);
    });
});

it('reads a message from its headers, taking the plain text and leaving out quoted history', function (): void {
    Http::fake([GMAIL.'/messages/gmail-1?format=full' => Http::response(gmailMessage(
        headers: [
            'From' => '=?UTF-8?B?S2ltIMOFa2Vy?= <Kim@Acme.test>',
            'To' => 'Help Desk <Help@Helpdesk.test>, other@acme.test',
            'Subject' => 'RE: Printer offline [#12]',
            'In-Reply-To' => '<sent-1@helpdesk.test>',
            'References' => '<msg-0@acme.test> <sent-1@helpdesk.test>',
        ],
        parts: [gmailPart('0', 'text/plain', "It is the one on level 2.\n\nOn Mon, 2 Nov 2026 at 09:14, Help Desk <help@helpdesk.test> wrote:\n> Which printer is it?\n")],
    ))]);

    $message = googleMailClient()->message('gmail-1');

    expect($message)->not->toBeNull()
        ->and($message?->externalId)->toBe('gmail-1')
        ->and($message?->messageId)->toBe('msg-1@acme.test')
        ->and($message?->inReplyTo)->toBe('sent-1@helpdesk.test')
        ->and($message?->references)->toBe(['msg-0@acme.test', 'sent-1@helpdesk.test'])
        ->and($message?->fromName)->toBe('Kim Åker')
        ->and($message?->fromAddress)->toBe('kim@acme.test')
        ->and($message?->to)->toBe(['help@helpdesk.test', 'other@acme.test'])
        ->and($message?->subject)->toBe('RE: Printer offline [#12]')
        ->and($message?->bodyText)->toStartWith('It is the one on level 2.')->toContain('> Which printer is it?')
        ->and($message?->replyText)->toBe('It is the one on level 2.')
        ->and($message?->receivedAt?->toIso8601String())->toBe('2026-11-18T01:00:00+00:00')
        ->and($message?->hasAttachments)->toBeFalse();
});

it('turns an HTML-only message into text, whatever its character set', function (): void {
    Http::fake([GMAIL.'/messages/gmail-1?format=full' => Http::response(gmailMessage(parts: [
        gmailPart('0', 'text/html', mb_convert_encoding('<p>Caf&eacute; printer is <b>offline</b>.<br>Señor Kim</p>', 'ISO-8859-1', 'UTF-8'), 'ISO-8859-1'),
    ]))]);

    expect(googleMailClient()->message('gmail-1')?->bodyText)->toBe("Café printer is offline.\nSeñor Kim");
});

it('has no message for a draft or one that has gone', function (): void {
    Http::fake([
        GMAIL.'/messages/gmail-draft?format=full' => Http::response(gmailMessage(['id' => 'gmail-draft', 'labelIds' => ['DRAFT']])),
        GMAIL.'/messages/gmail-gone?format=full' => Http::response(['error' => ['code' => 404, 'message' => 'Requested entity was not found.']], 404),
    ]);

    $client = googleMailClient();

    expect($client->message('gmail-draft'))->toBeNull()
        ->and($client->message('gmail-gone'))->toBeNull();
});

it('lists a message\'s files and fetches their contents', function (): void {
    Http::fake([
        GMAIL.'/messages/gmail-1?format=full' => Http::response(gmailMessage(parts: [
            gmailPart('0', 'text/plain', 'See attached.'),
            ['partId' => '1', 'mimeType' => 'application/pdf', 'filename' => 'invoice.pdf', 'headers' => [['name' => 'Content-Disposition', 'value' => 'attachment; filename="invoice.pdf"']], 'body' => ['size' => 2048, 'attachmentId' => 'att-1']],
            ['partId' => '2', 'mimeType' => 'image/png', 'filename' => 'logo.png', 'headers' => [['name' => 'Content-Disposition', 'value' => 'inline; filename="logo.png"']], 'body' => ['size' => 4, 'data' => gmailData("\x89PNG")]],
        ])),
        GMAIL.'/messages/gmail-1/attachments/att-1' => Http::response(['size' => 4, 'data' => gmailData('%PDF')]),
    ]);

    $client = googleMailClient();

    expect($client->message('gmail-1')?->hasAttachments)->toBeTrue()
        ->and($client->attachments('gmail-1'))->toBe([
            ['id' => 'att-1', 'name' => 'invoice.pdf', 'content_type' => 'application/pdf', 'size' => 2048, 'is_file' => true, 'is_inline' => false],
            ['id' => 'part:2', 'name' => 'logo.png', 'content_type' => 'image/png', 'size' => 4, 'is_file' => true, 'is_inline' => true],
        ])
        ->and($client->attachmentContents('gmail-1', 'att-1'))->toBe('%PDF')
        ->and($client->attachmentContents('gmail-1', 'part:2'))->toBe("\x89PNG");

    Http::assertSentCount(2);
});

it('sends a new thread from the connected account and keeps the id Gmail gave it', function (): void {
    Http::fake([
        GMAIL.'/messages/send' => Http::response(['id' => 'gmail-sent', 'threadId' => 'thread-9']),
        GMAIL.'/messages/gmail-sent?format=metadata*' => Http::response(['id' => 'gmail-sent', 'payload' => ['headers' => [['name' => 'Message-Id', 'value' => '<CAgmail-own-id@mail.gmail.com>']]]]),
    ]);

    $sent = googleMailClient()->send(new OutgoingMail(
        to: ['kim@acme.test'],
        bodyText: "We are on it.\n<b>Not markup</b>",
        subject: 'Printer offline',
        subjectTag: '[#12]',
    ));

    expect($sent->externalId)->toBe('gmail-sent')
        ->and($sent->internetMessageId)->toBe('CAgmail-own-id@mail.gmail.com')
        ->and($sent->subject)->toBe('Printer offline [#12]');

    Http::assertSent(function (Request $request): bool {
        if (! str_ends_with($request->url(), '/messages/send')) {
            return false;
        }

        $raw = gmailSentRaw($request);

        return $request->method() === 'POST'
            && ! isset($request['threadId'])
            && str_contains($raw, 'From: help@helpdesk.test')
            && str_contains($raw, 'To: kim@acme.test')
            && str_contains($raw, 'Subject: Printer offline [#12]')
            && ! str_contains($raw, 'In-Reply-To:')
            && str_contains($raw, "We are on it.<br>\r\n&lt;b&gt;Not markup&lt;/b&gt;");
    });
});

it('sends a reply into the conversation of the message it answers', function (): void {
    Http::fake([
        GMAIL.'/messages/gmail-1?format=minimal' => Http::response(['id' => 'gmail-1', 'threadId' => 'thread-1']),
        GMAIL.'/messages/send' => Http::response(['id' => 'gmail-sent', 'threadId' => 'thread-1']),
        GMAIL.'/messages/gmail-sent?format=metadata*' => Http::response(['id' => 'gmail-sent', 'payload' => ['headers' => [['name' => 'Message-ID', 'value' => '<sent-2@mail.gmail.com>']]]]),
    ]);
    $mailbox = googleMailbox();
    $answered = MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create([
        'team_id' => $mailbox->getTeamId()->toString(),
        'mailbox_id' => $mailbox->getId()->toString(),
        'external_id' => 'gmail-1',
        'internet_message_id' => 'msg-1@acme.test',
        'reference_ids' => ['msg-0@acme.test'],
        'subject' => 'Printer offline',
    ]));

    $sent = ResolveMailClient::forMailbox($mailbox)->send(new OutgoingMail(['kim@acme.test'], 'Which printer is it?', 'A ticket title', '[#12]', $answered->toInboundMail()));

    expect($sent->subject)->toBe('Re: Printer offline [#12]')
        ->and($sent->internetMessageId)->toBe('sent-2@mail.gmail.com');

    Http::assertSent(function (Request $request): bool {
        if (! str_ends_with($request->url(), '/messages/send')) {
            return false;
        }

        $raw = gmailSentRaw($request);

        return $request['threadId'] === 'thread-1'
            && str_contains($raw, 'Subject: Re: Printer offline [#12]')
            && str_contains($raw, 'In-Reply-To: <msg-1@acme.test>')
            && str_contains($raw, 'References: <msg-0@acme.test> <msg-1@acme.test>');
    });
});

it('still counts a reply as sent, under the id it was given, when Gmail cannot be asked for its own', function (): void {
    Sleep::fake();
    Http::fake([
        GMAIL.'/messages/send' => Http::response(['id' => 'gmail-sent']),
        GMAIL.'/messages/gmail-sent?format=metadata*' => Http::response(['error' => ['message' => 'Backend Error']], 503),
    ]);

    $sent = googleMailClient()->send(new OutgoingMail(['kim@acme.test'], 'We are on it.', 'Printer offline', '[#12]'));

    expect($sent->externalId)->toBe('gmail-sent')
        ->and($sent->internetMessageId)->toEndWith('@helpdesk.test');
});

it('never sends twice, and says why it could not send', function (Closure $response, string $message): void {
    Http::fake([GMAIL.'/messages/send' => $response()]);

    expect(fn () => googleMailClient()->send(new OutgoingMail(['kim@acme.test'], 'We are on it.', 'Printer offline', '[#12]')))
        ->toThrow(CouldNotReachConnector::class, $message);

    Http::assertSentCount(1);
})->with([
    'Gmail is down' => [fn () => Http::response(['error' => ['message' => 'Backend Error']], 503), 'Google could not be reached. It will be tried again.'],
    'the token is no longer accepted' => [fn () => Http::response(['error' => ['message' => 'Invalid Credentials']], 401), 'Google no longer accepts the sign-in for this connector. Connect it again.'],
    'Gmail refuses' => [fn () => Http::response(['error' => ['message' => 'Request had insufficient authentication scopes.']], 403), 'Request had insufficient authentication scopes.'],
]);

it('opens a ticket from mail in a Google mailbox', function (): void {
    travelTo(CarbonImmutable::parse('2026-11-18 00:55:00', 'UTC'));
    $mailbox = googleMailbox();
    travelTo(CarbonImmutable::parse('2026-11-18 01:05:00', 'UTC'));
    Http::fake(fn (Request $request) => match (true) {
        str_contains($request->url(), '/messages?') => Http::response(['messages' => [['id' => 'gmail-1']]]),
        str_contains($request->url(), '/messages/gmail-1?format=full') => Http::response(gmailMessage()),
        default => Http::response(['error' => ['message' => 'unexpected']], 400),
    });

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->sole())
        ->team_id->toBe($mailbox->getTeamId()->toString())
        ->title->toBe('Printer offline')
        ->description->toBe('The printer on level 2 is offline.')
        ->requester_email->toBe('kim@acme.test')
        ->requester_name->toBe('Kim Lee')
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole()->external_id)->toBe('gmail-1');
});
