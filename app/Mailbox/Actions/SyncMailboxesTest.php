<?php

declare(strict_types=1);

use App\Mailbox\Actions\SyncMailboxes;
use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\MailboxEloquentModel;
use App\Team\TeamEloquentModel;
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

it("leaves a suspended team's mailboxes unread", function (): void {
    $active = MailboxEloquentModel::factory()->create();
    $suspended = MailboxEloquentModel::factory()->create();
    TeamEloquentModel::query()->whereKey($suspended->team_id)->update(['suspended_at' => now()]);
    Queue::fake([SyncMailboxJob::class]);

    expect(SyncMailboxes::run())->toBe(1);

    Queue::assertPushedTimes(SyncMailboxJob::class, 1);
    Queue::assertPushed(fn (SyncMailboxJob $job): bool => $job->mailboxId === $active->id);
});
