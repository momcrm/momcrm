<?php

declare(strict_types=1);

use App\Mailbox\Actions\GetMailboxAccessToken;
use App\Mailbox\Exceptions\CouldNotReachMailbox;
use App\Mailbox\Mailbox;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('disconnects a mailbox and forgets its access', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $model = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);
    $mailbox = Mailbox::fromEloquentModel($model);
    GetMailboxAccessToken::remember($mailbox, 'access-token', 3600);

    actingAs($owner)
        ->delete(route('mailboxes.destroy', $model->id))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test disconnected.']);

    expect($model->fresh())->toBeNull()
        ->and(fn () => GetMailboxAccessToken::forMailbox($mailbox))->toThrow(CouldNotReachMailbox::class);
});

it('forbids members and hides other teams\' mailboxes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $theirs = MailboxEloquentModel::factory()->create();

    actingAs($member)->delete(route('mailboxes.destroy', $mailbox->id))->assertForbidden();
    actingAs($owner)->delete(route('mailboxes.destroy', $theirs->id))->assertNotFound();

    expect($mailbox->fresh())->not->toBeNull()
        ->and($theirs->fresh())->not->toBeNull();
});
