<?php

declare(strict_types=1);

use App\Attachment\AttachmentEloquentModel;
use App\MailboxMessage\Actions\SaveMessageAttachments;
use App\MailboxMessage\MailboxMessage;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * One line of a message's attachment listing.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function listedAttachment(string $id, array $overrides = []): array
{
    return [
        '@odata.type' => '#microsoft.graph.fileAttachment',
        'id' => $id,
        'name' => "{$id}.pdf",
        'contentType' => 'application/pdf',
        'size' => 1200,
        'isInline' => false,
        ...$overrides,
    ];
}

/**
 * A kept email on a ticket, as Microsoft's message "graph-1".
 */
function emailOnTicket(): MailboxMessage
{
    $ticket = TicketEloquentModel::factory()->create();

    return MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create([
        'team_id' => $ticket->team_id,
        'ticket_id' => $ticket->id,
        'external_id' => 'graph-1',
        'from_name' => 'Sam Lee',
    ]));
}

it('saves the files on the ticket, images shown inside the email included, leaving out what is not a file and what is too big', function (): void {
    Http::preventStrayRequests();
    Storage::fake('local');
    $message = emailOnTicket();
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/quote/$value' => Http::response('quote-bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/photo/$value' => Http::response('photo-bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/screenshot/$value' => Http::response('screenshot-bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments?*' => Http::response(['value' => [
            listedAttachment('quote'),
            listedAttachment('screenshot', ['name' => 'screenshot.png', 'contentType' => 'image/png', 'isInline' => true]),
            listedAttachment('forwarded', ['@odata.type' => '#microsoft.graph.itemAttachment']),
            listedAttachment('video', ['size' => SaveMessageAttachments::MAX_BYTES + 1]),
            listedAttachment('photo', ['@odata.type' => 'microsoft.graph.fileAttachment', 'name' => 'photo.jpg', 'contentType' => 'image/jpeg', 'size' => SaveMessageAttachments::MAX_BYTES]),
        ]]),
    ]);

    expect(SaveMessageAttachments::fromMessage(mailClientWithToken(), $message))->toBe(3);

    $saved = AttachmentEloquentModel::query()->withoutGlobalScopes()->orderBy('name')->get();

    expect($saved->pluck('name')->all())->toBe(['photo.jpg', 'quote.pdf', 'screenshot.png'])
        ->and($saved->pluck('attachable_id')->unique()->all())->toBe([$message->getTicketId()->toString()])
        ->and($saved->pluck('mime_type')->all())->toBe(['image/jpeg', 'application/pdf', 'image/png'])
        ->and(Storage::disk('local')->get($saved[1]->path))->toBe('quote-bytes')
        ->and(Storage::disk('local')->get($saved[2]->path))->toBe('screenshot-bytes');

    Http::assertSentCount(4);
});

it('saves attached files before images shown inside the email when there are too many of both', function (): void {
    Http::preventStrayRequests();
    Storage::fake('local');
    $message = emailOnTicket();
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/*/$value' => Http::response('bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments?*' => Http::response(['value' => [
            ...array_map(
                fn (int $index): array => listedAttachment("logo-{$index}", ['name' => "logo-{$index}.png", 'isInline' => true]),
                range(1, SaveMessageAttachments::MAX_FILES),
            ),
            listedAttachment('quote'),
        ]]),
    ]);

    expect(SaveMessageAttachments::fromMessage(mailClientWithToken(), $message))->toBe(SaveMessageAttachments::MAX_FILES);

    $names = AttachmentEloquentModel::query()->withoutGlobalScopes()->pluck('name');

    expect($names)->toContain('quote.pdf')
        ->and($names)->not->toContain('logo-'.SaveMessageAttachments::MAX_FILES.'.png');
});

it('saves no more files than people may upload at once', function (): void {
    Http::preventStrayRequests();
    Storage::fake('local');
    $message = emailOnTicket();
    Http::fake([
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments/*/$value' => Http::response('bytes'),
        'graph.microsoft.com/v1.0/me/messages/graph-1/attachments?*' => Http::response([
            'value' => array_map(fn (int $index): array => listedAttachment("file-{$index}"), range(1, SaveMessageAttachments::MAX_FILES + 2)),
        ]),
    ]);

    expect(SaveMessageAttachments::fromMessage(mailClientWithToken(), $message))->toBe(SaveMessageAttachments::MAX_FILES)
        ->and(AttachmentEloquentModel::query()->withoutGlobalScopes()->count())->toBe(SaveMessageAttachments::MAX_FILES);

    Http::assertSentCount(SaveMessageAttachments::MAX_FILES + 1);
});

it('asks for nothing when the email is on no ticket', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    $message = MailboxMessage::fromEloquentModel(MailboxMessageEloquentModel::factory()->create(['status' => 'ignored']));

    expect(SaveMessageAttachments::fromMessage(mailClientWithToken(), $message))->toBe(0);

    Http::assertNothingSent();
});
