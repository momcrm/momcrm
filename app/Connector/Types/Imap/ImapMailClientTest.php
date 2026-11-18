<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Connector\ConnectorEloquentModel;
use App\Connector\Types\Imap\ImapConnector;
use App\Connector\Exceptions\CouldNotReachConnector;
use App\Mailbox\Actions\ResolveMailClient;
use App\Mailbox\Actions\SyncMailbox;
use App\Connector\Types\Imap\MailServers;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Mailbox\OutgoingMail;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeMailServers;

use function Pest\Laravel\travelTo;

beforeEach(function (): void {
    app()->instance(MailServers::class, $this->servers = new FakeMailServers);
});

/**
 * A mailbox that goes through an IMAP/SMTP connector.
 */
function imapMailbox(): Mailbox
{
    $connector = ConnectorEloquentModel::factory()->imap()->create(['account' => 'help@helpdesk.test']);

    return Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create([
        'team_id' => $connector->team_id,
        'connector_id' => $connector->id,
        'address' => 'help@helpdesk.test',
    ]));
}

it('signs in with the connector\'s servers and passwords', function (): void {
    ResolveMailClient::forMailbox(imapMailbox())->checkInbox();

    expect($this->servers->imapSignIns)->toHaveCount(1)
        ->and($this->servers->imapSignIns[0])
        ->fromAddress->toBe('help@helpdesk.test')
        ->fromName->toBe('Help Desk')
        ->imapHost->toBe('203.0.113.10')
        ->imapPort->toBe(993)
        ->imapEncryption->toBe('ssl')
        ->imapUsername->toBe('help')
        ->imapPassword->toBe('imap-secret')
        ->smtpHost->toBe('203.0.113.20')
        ->smtpPassword->toBe('smtp-secret');
});

it('lists what arrived since a time, oldest first, leaving out the earlier mail of that day', function (): void {
    $this->servers
        ->deliver(3, '2026-11-18 03:00:00')
        ->deliver(1, '2026-11-18 00:30:00')
        ->deliver(2, '2026-11-18 02:00:00')
        ->deliver(4, '2026-11-18 04:00:00');

    $messages = ResolveMailClient::forMailbox(imapMailbox())->inboxMessagesSince(CarbonImmutable::parse('2026-11-18 01:00:00', 'UTC'), 2);

    expect(array_column($messages, 'id'))->toBe(['7:2', '7:3'])
        ->and($messages[0]['received_at']->toIso8601String())->toBe('2026-11-18T02:00:00+00:00')
        ->and($this->servers->fetched)->toBe([]);
});

it('reads a message from its headers and text, once', function (): void {
    $this->servers->deliver(5, '2026-11-18 01:00:00', [
        'subject' => 'RE: Printer offline [#12]',
        'in-reply-to' => '<sent-1@helpdesk.test>',
        'references' => '<msg-0@acme.test> <sent-1@helpdesk.test>',
    ], "It is the one on level 2.\n\n-----Original Message-----\nWhich printer is it?");
    $client = ResolveMailClient::forMailbox(imapMailbox());

    $message = $client->message('7:5');
    $client->message('7:5');

    expect($message?->getExternalId()->toString())->toBe('7:5')
        ->and($message?->getInternetMessageId()->toString())->toBe('msg-5@acme.test')
        ->and($message?->getInReplyTo()->toString())->toBe('sent-1@helpdesk.test')
        ->and($message?->getReferenceIds()->toArray())->toBe(['msg-0@acme.test', 'sent-1@helpdesk.test'])
        ->and($message?->getFromName()->toString())->toBe('Kim Lee')
        ->and($message?->getFromAddress()->toString())->toBe('kim@acme.test')
        ->and($message?->getSubject()->toString())->toBe('RE: Printer offline [#12]')
        ->and($message?->getReplyText()->toString())->toBe('It is the one on level 2.')
        ->and($message?->getReceivedAt()->toCarbon()->toIso8601String())->toBe('2026-11-18T01:00:00+00:00')
        ->and($message?->getHasAttachments()->toBoolean())->toBeFalse()
        ->and($this->servers->fetched)->toBe([5]);
});

it('turns a message with only HTML into text', function (): void {
    $this->servers->deliver(5, '2026-11-18 01:00:00', text: null, html: '<p>The printer is <b>offline</b>.</p>');

    expect(ResolveMailClient::forMailbox(imapMailbox())->message('7:5')?->getBodyText()->toString())->toBe('The printer is offline.');
});

it('has no message for a draft, one that has gone, or one numbered in an inbox that was rebuilt', function (): void {
    $this->servers
        ->deliver(5, '2026-11-18 01:00:00', isDraft: true)
        ->deliver(6, '2026-11-18 01:00:00');
    $client = ResolveMailClient::forMailbox(imapMailbox());

    expect($client->message('7:5'))->toBeNull()
        ->and($client->message('7:99'))->toBeNull()
        ->and($client->message('8:6'))->toBeNull()
        ->and($client->message('not-an-id'))->toBeNull()
        ->and($client->message('7:6'))->not->toBeNull();
});

it('lists a message\'s files and hands over their contents', function (): void {
    $this->servers->deliver(5, '2026-11-18 01:00:00', attachments: [
        ['name' => 'invoice.pdf', 'content_type' => 'application/pdf', 'size' => 4, 'is_inline' => false, 'content' => '%PDF'],
        ['name' => 'logo.png', 'content_type' => 'image/png', 'size' => 4, 'is_inline' => true, 'content' => "\x89PNG"],
    ]);
    $client = ResolveMailClient::forMailbox(imapMailbox());

    expect($client->message('7:5')?->getHasAttachments()->toBoolean())->toBeTrue()
        ->and($client->attachments('7:5'))->toBe([
            ['id' => '0', 'name' => 'invoice.pdf', 'content_type' => 'application/pdf', 'size' => 4, 'is_file' => true, 'is_inline' => false],
            ['id' => '1', 'name' => 'logo.png', 'content_type' => 'image/png', 'size' => 4, 'is_file' => true, 'is_inline' => true],
        ])
        ->and($client->attachmentContents('7:5', '1'))->toBe("\x89PNG")
        ->and(fn () => $client->attachmentContents('7:5', '2'))->toThrow(CouldNotReachConnector::class, 'That attachment is no longer on the message.')
        ->and($this->servers->fetched)->toBe([5]);
});

it('sends a new thread over SMTP from the mailbox\'s name and address', function (): void {
    $sent = ResolveMailClient::forMailbox(imapMailbox())->send(new OutgoingMail(['kim@acme.test'], "We are on it.\nBack soon.", 'Printer offline', '[#12]', MailboxMessage::new()));

    $email = $this->servers->sent[0];

    expect($this->servers->sent)->toHaveCount(1)
        ->and($email->getFrom()[0]->toString())->toBe('"Help Desk" <help@helpdesk.test>')
        ->and($email->getTo()[0]->getAddress())->toBe('kim@acme.test')
        ->and($email->getSubject())->toBe('Printer offline [#12]')
        ->and($email->getTextBody())->toBe("We are on it.\nBack soon.")
        ->and($email->getHtmlBody())->toBe("We are on it.<br>\nBack soon.")
        ->and($email->getHeaders()->has('In-Reply-To'))->toBeFalse()
        ->and($sent->externalId)->toBeNull()
        ->and($sent->subject)->toBe('Printer offline [#12]')
        ->and($sent->internetMessageId)->toEndWith('@helpdesk.test')
        ->and($email->getHeaders()->getHeaderBody('Message-ID'))->toBe([$sent->internetMessageId])
        ->and($this->servers->smtpSignIns[0]->smtpPassword)->toBe('smtp-secret');
});

it('sends a reply that names the message it answers and the thread before it', function (): void {
    $mailbox = imapMailbox();
    $answered = MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create([
        'team_id' => $mailbox->getTeamId()->toString(),
        'mailbox_id' => $mailbox->getId()->toString(),
        'external_id' => '7:5',
        'internet_message_id' => 'msg-5@acme.test',
        'reference_ids' => ['msg-0@acme.test'],
        'subject' => 'Printer offline',
    ]));

    $sent = ResolveMailClient::forMailbox($mailbox)->send(new OutgoingMail(['kim@acme.test'], 'Which printer is it?', 'A ticket title', '[#12]', $answered));

    $headers = $this->servers->sent[0]->getHeaders();

    expect($sent->subject)->toBe('Re: Printer offline [#12]')
        ->and($headers->getHeaderBody('In-Reply-To'))->toBe(['msg-5@acme.test'])
        ->and($headers->getHeaderBody('References'))->toBe(['msg-0@acme.test', 'msg-5@acme.test']);
});

it('passes on why a reply could not be sent, and sends nothing', function (): void {
    $this->servers->smtpFailure = CouldNotReachConnector::unavailable(ImapConnector::COMPANY);

    expect(fn () => ResolveMailClient::forMailbox(imapMailbox())->send(new OutgoingMail(['kim@acme.test'], 'We are on it.', 'Printer offline', '[#12]', MailboxMessage::new())))
        ->toThrow(CouldNotReachConnector::class, 'The mail server could not be reached. It will be tried again.');

    expect($this->servers->sent)->toBe([])
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('connected');
});

it('asks for a reconnect once a server refuses the stored username and password', function (Closure $fail, Closure $use): void {
    $mailbox = imapMailbox();
    $fail($this->servers);

    expect(fn () => $use(ResolveMailClient::forMailbox($mailbox)))
        ->toThrow(CouldNotReachConnector::class, 'The mail server no longer accepts the sign-in for this connector. Connect it again.');

    expect(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole())
        ->status->toBe('needs_reconnect')
        ->last_error->toBe('The mail server no longer accepts the sign-in for this connector. Connect it again.');
})->with([
    'reading' => [
        fn (FakeMailServers $servers) => $servers->imapFailure = CouldNotReachConnector::needsReconnect(ImapConnector::COMPANY),
        fn ($client) => $client->inboxMessagesSince(CarbonImmutable::now(), 10),
    ],
    'sending' => [
        fn (FakeMailServers $servers) => $servers->smtpFailure = CouldNotReachConnector::needsReconnect(ImapConnector::COMPANY),
        fn ($client) => $client->send(new OutgoingMail(['kim@acme.test'], 'We are on it.', 'Printer offline', '[#12]', MailboxMessage::new())),
    ],
]);

it('opens a ticket, with its files, from mail in an IMAP mailbox, and does not take it in twice', function (): void {
    Storage::fake('local');
    travelTo(CarbonImmutable::parse('2026-11-18 00:55:00', 'UTC'));
    $mailbox = imapMailbox();
    travelTo(CarbonImmutable::parse('2026-11-18 01:05:00', 'UTC'));
    $this->servers
        ->deliver(4, '2026-11-18 00:10:00', ['subject' => 'From before the mailbox was added'])
        ->deliver(5, '2026-11-18 01:00:00', attachments: [
            ['name' => 'photo.png', 'content_type' => 'image/png', 'size' => 4, 'is_inline' => false, 'content' => "\x89PNG"],
        ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1)
        ->and(SyncMailbox::fromMailbox(Mailbox::fromEloquentModel(MailboxEloquentModel::query()->withoutGlobalScopes()->sole())))->toBe(0);

    $ticket = TicketEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($ticket)
        ->title->toBe('Printer offline')
        ->description->toBe('The printer on level 2 is offline.')
        ->requester_email->toBe('kim@acme.test')
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->sole()->external_id)->toBe('7:5')
        ->and(AttachmentEloquentModel::query()->withoutGlobalScopes()->sole()->name)->toBe('photo.png')
        ->and($this->servers->fetched)->toBe([5]);
});
