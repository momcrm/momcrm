<?php

declare(strict_types=1);

use App\Group\GroupEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves where a mailbox\'s tickets go and whether it opens them', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $admin = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Admin)->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'address' => 'support@acme.test']);

    actingAs($admin)
        ->put(route('mailboxes.update', $mailbox->id), ['group_id' => $group->id, 'auto_create_tickets' => false])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('mailboxes.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'support@acme.test saved.']);

    expect($mailbox->refresh())
        ->group_id->toBe($group->id)
        ->auto_create_tickets->toBeFalse()
        ->address->toBe('support@acme.test')
        ->refresh_token->toBe('refresh-token');
});

it('clears the group', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'group_id' => $group->id]);

    actingAs($owner)
        ->put(route('mailboxes.update', $mailbox->id), ['group_id' => null, 'auto_create_tickets' => true])
        ->assertSessionHasNoErrors();

    expect($mailbox->refresh()->group_id)->toBeNull();
});

it('rejects a group from another team and a missing choice', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->put(route('mailboxes.update', $mailbox->id), ['group_id' => GroupEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors([
            'group_id' => 'The selected group id is invalid.',
            'auto_create_tickets' => 'The auto create tickets field is required.',
        ]);

    expect($mailbox->refresh()->group_id)->toBeNull();
});

it('forbids members and hides other teams\' mailboxes', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $theirs = MailboxEloquentModel::factory()->create();
    $payload = ['group_id' => null, 'auto_create_tickets' => false];

    actingAs($member)->put(route('mailboxes.update', $mailbox->id), $payload)->assertForbidden();
    actingAs($owner)->put(route('mailboxes.update', $theirs->id), $payload)->assertNotFound();

    expect($mailbox->refresh()->auto_create_tickets)->toBeTrue()
        ->and($theirs->refresh()->auto_create_tickets)->toBeTrue();
});
