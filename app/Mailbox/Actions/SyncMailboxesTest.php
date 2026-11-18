<?php

declare(strict_types=1);

use App\Mailbox\Actions\SyncMailboxes;
use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\MailboxEloquentModel;
use Illuminate\Support\Facades\Queue;

it('queues a read of every connected mailbox, in every team', function (): void {
    $first = MailboxEloquentModel::factory()->create();
    $second = MailboxEloquentModel::factory()->create();
    MailboxEloquentModel::factory()->needsReconnect()->create();
    Queue::fake([SyncMailboxJob::class]);

    expect(SyncMailboxes::run())->toBe(2);

    Queue::assertPushedTimes(SyncMailboxJob::class, 2);
    Queue::assertPushed(fn (SyncMailboxJob $job): bool => $job->mailboxId === $first->id);
    Queue::assertPushed(fn (SyncMailboxJob $job): bool => $job->mailboxId === $second->id);
});
