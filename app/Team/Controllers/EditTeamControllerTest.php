<?php

declare(strict_types=1);

use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the owner the members and pending invitations', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Ann Owner', 'email' => 'ann@acme.test']);
    $member = UserEloquentModel::factory()->create(['name' => 'Bob Member', 'email' => 'bob@acme.test']);
    CreateTeamUser::fromIds(teamId: $owner->current_team_id, userId: $member->id);
    TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'email' => 'sam@acme.test']);
    TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'accepted_at' => now()]);
    TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'expires_at' => now()->subDay()]);

    actingAs($owner)
        ->get(route('team.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/team')
            ->where('canInvite', true)
            ->where('members', [
                ['id' => $owner->id, 'name' => 'Ann Owner', 'email' => 'ann@acme.test', 'role' => 'owner'],
                ['id' => $member->id, 'name' => 'Bob Member', 'email' => 'bob@acme.test', 'role' => 'member'],
            ])
            ->has('invitations', 1)
            ->where('invitations.0.email', 'sam@acme.test'),
        );
});

it('hides invitations from members who cannot invite', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $owner->current_team_id, userId: $member->id, role: TeamRoleEnum::Member);
    $member->forceFill(['current_team_id' => $owner->current_team_id])->save();
    TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)
        ->get(route('team.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canInvite', false)
            ->where('invitations', []),
        );
});
