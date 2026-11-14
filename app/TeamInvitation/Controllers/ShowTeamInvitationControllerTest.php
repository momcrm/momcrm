<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamInvitation\Listeners\AcceptPendingTeamInvitation;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function invitationWithToken(string $token, array $attributes = []): TeamInvitationEloquentModel
{
    return TeamInvitationEloquentModel::factory()->create([
        'team_id' => TeamEloquentModel::factory()->create(['name' => 'Acme IT'])->id,
        'email' => 'sam@acme.test',
        'token_hash' => hash('sha256', $token),
        ...$attributes,
    ]);
}

it('asks guests to sign up or log in and remembers the invitation', function (): void {
    invitationWithToken('secret-token');

    get(route('team-invitations.show', 'secret-token'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/team-invitation')
            ->where('status', 'guest')
            ->where('teamName', 'Acme IT')
            ->where('email', 'sam@acme.test'),
        )
        ->assertSessionHas(AcceptPendingTeamInvitation::SESSION_KEY, 'secret-token');
});

it('offers to join when signed in as the invited address', function (): void {
    invitationWithToken('secret-token');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'sam@acme.test']))
        ->get(route('team-invitations.show', 'secret-token'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'valid'));
});

it('explains when signed in as someone else', function (): void {
    invitationWithToken('secret-token');

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create(['email' => 'other@acme.test']))
        ->get(route('team-invitations.show', 'secret-token'))
        ->assertInertia(fn (Assert $page) => $page->where('status', 'wrong-account'));
});

it('says when an invitation has expired, been used or does not exist', function (array $attributes): void {
    invitationWithToken('secret-token', $attributes);

    get(route('team-invitations.show', 'secret-token'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('status', 'expired')->missing('teamName'));
})->with([
    'expired' => [['expires_at' => now()->subMinute()]],
    'accepted' => [['accepted_at' => now()]],
    'unknown token' => [['token_hash' => hash('sha256', 'another-token')]],
]);
