<?php

declare(strict_types=1);

use App\TeamInvitation\Listeners\AcceptPendingTeamInvitation;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\Actions\FindTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\withSession;

it('joins the team when a guest who opened the invitation signs up', function (): void {
    $invitation = TeamInvitationEloquentModel::factory()->create(['email' => 'sam@acme.test', 'token_hash' => hash('sha256', 'secret-token')]);

    withSession([AcceptPendingTeamInvitation::SESSION_KEY => 'secret-token'])->post(route('register.store'), [
        'name' => 'Sam Lee',
        'email' => 'sam@acme.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = UserEloquentModel::query()->where('email', 'sam@acme.test')->sole();
    expect(FindTeamUser::byTeamAndUser(teamId: $invitation->team_id, userId: $user->id)->isNotNull())->toBeTrue()
        ->and($user->current_team_id)->toBe($invitation->team_id)
        ->and($user->onboarded_at)->not->toBeNull();
});

it('joins the team when a guest who opened the invitation logs in', function (): void {
    $invitation = TeamInvitationEloquentModel::factory()->create(['email' => 'sam@acme.test', 'token_hash' => hash('sha256', 'secret-token')]);
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']);

    withSession([AcceptPendingTeamInvitation::SESSION_KEY => 'secret-token'])->post(route('login.store'), [
        'email' => 'sam@acme.test',
        'password' => 'password',
    ]);

    expect($user->fresh()->current_team_id)->toBe($invitation->team_id);
});

it('ignores the invitation when someone else logs in', function (): void {
    $invitation = TeamInvitationEloquentModel::factory()->create(['email' => 'sam@acme.test', 'token_hash' => hash('sha256', 'secret-token')]);
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'other@acme.test']);

    withSession([AcceptPendingTeamInvitation::SESSION_KEY => 'secret-token'])->post(route('login.store'), [
        'email' => 'other@acme.test',
        'password' => 'password',
    ]);

    expect(FindTeamUser::byTeamAndUser(teamId: $invitation->team_id, userId: $user->id)->isNull())->toBeTrue();
});
