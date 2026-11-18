<?php

declare(strict_types=1);

use App\Connector\ConnectorEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('removes a mailbox and leaves its connector connected', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);

    actingAs($owner)
        ->delete(route('mailboxes.destroy', $mailbox->id))
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test removed.']);

    expect($mailbox->fresh())->toBeNull()
        ->and(ConnectorEloquentModel::query()->withoutGlobalScopes()->find($mailbox->connector_id)?->status)->toBe('connected');
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
