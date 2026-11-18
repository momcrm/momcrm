<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\Connector\ConnectorEloquentModel;
use App\Mailbox\Actions\SyncMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mom\Connectors\CouldNotReachConnector;

use function Pest\Laravel\freezeSecond;
use function Pest\Laravel\travelTo;

const INBOX_MESSAGES = 'graph.microsoft.com/v1.0/me/mailFolders/inbox/messages*';

/**
 * A connected mailbox we already hold a token for, so only Graph is called.
 *
 * @param  array<string, mixed>  $attributes
 */
function mailboxToSync(array $attributes = []): Mailbox
{
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->create($attributes));
    rememberMailboxToken($mailbox);

    return $mailbox;
}

/**
 * One line of the inbox listing.
 *
 * @return array{id: string, receivedDateTime: string}
 */
function listed(string $id, string $receivedAt = '2026-11-18T01:15:00Z'): array
{
    return ['id' => $id, 'receivedDateTime' => $receivedAt];
}

it('opens a ticket for each new message and remembers how far it read', function (): void {
    Http::preventStrayRequests();
    travelTo('2026-11-18 01:20:00');
    $now = CarbonImmutable::now();
    $mailbox = mailboxToSync([
        'created_at' => '2026-11-18 01:00:00',
        'synced_through' => '2026-11-18 01:00:00',
        'last_error' => 'Microsoft could not be reached. It will be tried again.',
    ]);
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1'), listed('graph-2', '2026-11-18T01:18:00Z')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1*' => Http::response(graphMessage()),
        'graph.microsoft.com/v1.0/me/messages/graph-2*' => Http::response(graphMessage([
            'id' => 'graph-2',
            'subject' => 'New starter on Monday',
            'internetMessageId' => '<message-2@acme.test>',
        ])),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(2)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->orderBy('number')->pluck('title')->all())
        ->toBe(['Printer offline', 'New starter on Monday']);

    $model = MailboxEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($model->synced_through?->equalTo($now))->toBeTrue()
        ->and($model->last_synced_at?->equalTo($now))->toBeTrue()
        ->and($model->last_error)->toBeNull();

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/mailFolders/inbox/messages')
        && $request['$filter'] === 'receivedDateTime ge 2026-11-18T01:00:00Z'
        && $request['$orderby'] === 'receivedDateTime asc');
});

it('looks a little way back past the last read, and fetches only what it has not kept', function (): void {
    Http::preventStrayRequests();
    travelTo('2026-11-18 01:05:00');
    $mailbox = mailboxToSync(['created_at' => '2026-11-18 00:00:00', 'synced_through' => '2026-11-18 01:00:00']);
    MailboxMessageEloquentModel::factory()->create(['mailbox_id' => $mailbox->getId()->toString(), 'external_id' => 'graph-1']);
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1', '2026-11-18T00:55:00Z'), listed('graph-2')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-2*' => Http::response(graphMessage(['id' => 'graph-2'])),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1);

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => ($request['$filter'] ?? null) === 'receivedDateTime ge 2026-11-18T00:50:00Z');
});

it('never reads mail from before the mailbox was connected', function (): void {
    Http::preventStrayRequests();
    travelTo('2026-11-18 01:05:00');
    $mailbox = mailboxToSync(['created_at' => '2026-11-18 01:00:00', 'synced_through' => '2026-11-18 01:04:00']);
    Http::fake([INBOX_MESSAGES => Http::response(['value' => []])]);

    SyncMailbox::fromMailbox($mailbox);

    Http::assertSent(fn (Request $request): bool => $request['$filter'] === 'receivedDateTime ge 2026-11-18T01:00:00Z');
});

it('carries on from the last message when a read is full', function (): void {
    Http::preventStrayRequests();
    travelTo('2026-11-18 03:00:00');
    $mailbox = mailboxToSync(['created_at' => '2026-11-18 01:00:00', 'synced_through' => '2026-11-18 01:00:00']);
    Http::fake(function (Request $request) {
        if (str_contains($request->url(), '/mailFolders/inbox/messages')) {
            return Http::response([
                'value' => array_map(
                    fn (int $index): array => listed("graph-{$index}", sprintf('2026-11-18T02:%02d:00Z', $index)),
                    range(0, SyncMailbox::MAX_MESSAGES - 1),
                ),
                '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?$skip=50',
            ]);
        }

        $id = urldecode(basename((string) parse_url($request->url(), PHP_URL_PATH)));

        return Http::response(graphMessage(['id' => $id, 'internetMessageId' => "<{$id}@acme.test>"]));
    });

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(SyncMailbox::MAX_MESSAGES);

    $model = MailboxEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($model->synced_through?->toIso8601ZuluString())->toBe('2026-11-18T02:49:00Z')
        ->and($model->last_synced_at?->toIso8601ZuluString())->toBe('2026-11-18T03:00:00Z');

    Http::assertSentCount(SyncMailbox::MAX_MESSAGES + 1);
});

it('keeps its place and says why when Microsoft cannot be reached', function (): void {
    Http::preventStrayRequests();
    $synced = freezeSecond();
    $mailbox = mailboxToSync();
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1'), listed('graph-2')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1*' => Http::response(graphMessage()),
        'graph.microsoft.com/v1.0/me/messages/graph-2*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1);

    $model = MailboxEloquentModel::query()->withoutGlobalScopes()->sole();

    expect($model->last_error)->toBe('Access is denied.')
        ->and($model->last_synced_at)->toBeNull()
        ->and($model->synced_through?->equalTo($synced))->toBeTrue()
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->sole()->status)->toBe('connected');
});

it('skips drafts and messages that have gone', function (): void {
    Http::preventStrayRequests();
    $mailbox = mailboxToSync();
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1'), listed('graph-2')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1*' => Http::response(graphMessage(['isDraft' => true])),
        'graph.microsoft.com/v1.0/me/messages/graph-2*' => Http::response(['error' => ['code' => 'ErrorItemNotFound']], 404),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(0)
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse()
        ->and(MailboxEloquentModel::query()->withoutGlobalScopes()->sole()->last_error)->toBeNull();
});

it('reports a message it cannot take in and carries on with the rest', function (): void {
    Http::preventStrayRequests();
    Exceptions::fake();
    $mailbox = mailboxToSync();
    TicketEloquentModel::creating(function (TicketEloquentModel $ticket): void {
        if ($ticket->title === 'Poison') {
            throw new RuntimeException('Could not save the ticket.');
        }
    });
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1'), listed('graph-2')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1*' => Http::response(graphMessage(['subject' => 'Poison'])),
        'graph.microsoft.com/v1.0/me/messages/graph-2*' => Http::response(graphMessage(['id' => 'graph-2', 'internetMessageId' => '<message-2@acme.test>'])),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->pluck('title')->all())->toBe(['Printer offline'])
        ->and(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->pluck('external_id')->all())->toBe(['graph-2'])
        ->and(MailboxEloquentModel::query()->withoutGlobalScopes()->sole()->last_synced_at)->not->toBeNull();

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Could not save the ticket.');
});

it('saves an email\'s attachments on the ticket it opens', function (): void {
    Http::preventStrayRequests();
    Storage::fake('local');
    $mailbox = mailboxToSync();
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/file-1/$value' => Http::response('pdf-bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments?*' => Http::response(['value' => [[
            '@odata.type' => '#microsoft.graph.fileAttachment',
            'id' => 'file-1',
            'name' => 'quote.pdf',
            'contentType' => 'application/pdf',
            'size' => 9,
            'isInline' => false,
        ]]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1?*' => Http::response(graphMessage(['hasAttachments' => true])),
    ]);

    SyncMailbox::fromMailbox($mailbox);

    expect(AttachmentEloquentModel::query()->withoutGlobalScopes()->sole())
        ->name->toBe('quote.pdf')
        ->attachable_id->toBe(TicketEloquentModel::query()->withoutGlobalScopes()->sole()->id);
});

it('still opens the ticket, and reports it, when the attachments cannot be fetched', function (): void {
    Http::preventStrayRequests();
    Exceptions::fake();
    $mailbox = mailboxToSync();
    Http::fake([
        INBOX_MESSAGES => Http::response(['value' => [listed('graph-1')]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments?*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403),
        'graph.microsoft.com/v1.0/me/messages/graph-1?*' => Http::response(graphMessage(['hasAttachments' => true])),
    ]);

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(1)
        ->and(TicketEloquentModel::query()->withoutGlobalScopes()->count())->toBe(1)
        ->and(AttachmentEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse()
        ->and(MailboxEloquentModel::query()->withoutGlobalScopes()->sole()->last_error)->toBeNull();

    Exceptions::assertReported(fn (CouldNotReachConnector $exception): bool => $exception->getMessage() === 'Access is denied.');
});

it('does not read a mailbox that needs reconnecting', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    $mailbox = Mailbox::fromEloquentModel(MailboxEloquentModel::factory()->needsReconnect()->create());

    expect(SyncMailbox::fromMailbox($mailbox))->toBe(0);

    Http::assertNothingSent();
});
