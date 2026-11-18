<?php

declare(strict_types=1);

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\Ticket\TicketEloquentModel;
use Illuminate\Support\Facades\Http;

it('reads the mailbox it was queued for', function (): void {
    Http::preventStrayRequests();
    $model = MailboxEloquentModel::factory()->create();
    GetMailboxAccessToken::remember(Mailbox::fromEloquentModel($model), 'access-token', 3600);
    Http::fake([
        'graph.microsoft.com/v1.0/me/mailFolders/inbox/messages*' => Http::response(['value' => [['id' => 'graph-1', 'receivedDateTime' => '2026-11-18T01:15:00Z']]]),
        'graph.microsoft.com/v1.0/me/messages/graph-1*' => Http::response(graphMessage()),
    ]);

    SyncMailboxJob::dispatch($model->id);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->sole()->mailbox_id)->toBe($model->id);
});

it('does nothing for a mailbox disconnected since it was queued', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    $model = MailboxEloquentModel::factory()->create();
    $model->delete();

    SyncMailboxJob::dispatch($model->id);

    Http::assertNothingSent();
});

it('is queued once per mailbox at a time', function (): void {
    expect(new SyncMailboxJob('mailbox-1')->uniqueId())->toBe('mailbox-1')
        ->and(new SyncMailboxJob('mailbox-2')->uniqueId())->toBe('mailbox-2');
});
