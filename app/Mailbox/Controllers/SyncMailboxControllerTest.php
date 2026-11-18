<?php

declare(strict_types=1);

use App\Mailbox\Jobs\SyncMailboxJob;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

it('queues a read of the mailbox', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);
    Queue::fake([SyncMailboxJob::class]);

    actingAs($owner)
        ->post(route('mailboxes.sync', $mailbox->id))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Checking support@acme.test for new mail.']);

    Queue::assertPushedTimes(SyncMailboxJob::class, 1);
    Queue::assertPushed(fn (SyncMailboxJob $job): bool => $job->mailboxId === $mailbox->id);
});

it('asks for a reconnect first when the mailbox\'s connector needs one', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->needsReconnect()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);
    Queue::fake([SyncMailboxJob::class]);

    actingAs($owner)
        ->post(route('mailboxes.sync', $mailbox->id))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Reconnect the account support@acme.test goes through before checking it for mail.']);

    Queue::assertNotPushed(SyncMailboxJob::class);
});

it('forbids members and hides other teams\' mailboxes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $theirs = MailboxEloquentModel::factory()->create();
    Queue::fake([SyncMailboxJob::class]);

    actingAs($member)->post(route('mailboxes.sync', $mailbox->id))->assertForbidden();
    actingAs($owner)->post(route('mailboxes.sync', $theirs->id))->assertNotFound();

    Queue::assertNotPushed(SyncMailboxJob::class);
});
