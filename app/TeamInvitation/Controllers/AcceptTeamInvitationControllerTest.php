<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Group\GroupEloquentModel;
use App\Team\TeamEloquentModel;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds the user to the team and switches to it', function (): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Acme IT']);
    $invitation = TeamInvitationEloquentModel::factory()->create([
        'team_id' => $team->id,
        'email' => 'sam@acme.test',
        'role' => TeamRoleEnum::Admin->value,
        'token_hash' => hash('sha256', 'secret-token'),
    ]);
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);

    $response = actingAs($user)->post(route('team-invitations.accept', 'secret-token'));

    $response->assertRedirectToRoute('dashboard');
    expect(FindTeamUser::byTeamAndUser(teamId: $team->id, userId: $user->id)->getRole()->toEnum())->toBe(TeamRoleEnum::Admin);
    expect($user->fresh()->current_team_id)->toBe($team->id);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::InvitationAccepted->value)->sole()->team_id)->toBe($team->id);
});

it('lets someone who joins a team skip the workspace onboarding steps', function (): void {
    TeamInvitationEloquentModel::factory()->create(['email' => 'sam@acme.test', 'token_hash' => hash('sha256', 'secret-token')]);
    $user = UserEloquentModel::factory()->notOnboarded()->withPersonalTeam()->create(['email' => 'sam@acme.test']);

    actingAs($user)->post(route('team-invitations.accept', 'secret-token'));

    expect($user->fresh()->onboarded_at)->not->toBeNull();
});

it('forbids accepting an invitation sent to another address', function (): void {
    $invitation = TeamInvitationEloquentModel::factory()->create(['email' => 'sam@acme.test', 'token_hash' => hash('sha256', 'secret-token')]);
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'other@acme.test']);

    actingAs($user)
        ->post(route('team-invitations.accept', 'secret-token'))
        ->assertForbidden();

    expect(FindTeamUser::byTeamAndUser(teamId: $invitation->team_id, userId: $user->id)->isNull())->toBeTrue();
});

it('returns not found for an expired invitation', function (): void {
    TeamInvitationEloquentModel::factory()->create([
        'email' => 'sam@acme.test',
        'token_hash' => hash('sha256', 'secret-token'),
        'expires_at' => now()->subDay(),
    ]);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']))
        ->post(route('team-invitations.accept', 'secret-token'))
        ->assertNotFound();
});

it('adds the user to the group the invitation names', function (): void {
    $team = TeamEloquentModel::factory()->create();
    $group = GroupEloquentModel::factory()->create(['team_id' => $team->id]);
    TeamInvitationEloquentModel::factory()->create([
        'team_id' => $team->id,
        'email' => 'sam@acme.test',
        'group_id' => $group->id,
        'token_hash' => hash('sha256', 'secret-token'),
    ]);
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);

    actingAs($user)->post(route('team-invitations.accept', 'secret-token'))->assertRedirectToRoute('dashboard');

    expect($group->members()->sole()->id)->toBe($user->id)
        ->and($group->members()->sole()->pivot->role)->toBe('member');
});
