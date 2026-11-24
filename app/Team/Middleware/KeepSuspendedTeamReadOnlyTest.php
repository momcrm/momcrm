<?php

declare(strict_types=1);

use App\Team\TeamEloquentModel;
use App\TeamUser\Actions\CreateTeamUser;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;

/**
 * The owner of a suspended team named Acme Sales.
 */
function ownerOfSuspendedTeam(): UserEloquentModel
{
    $team = TeamEloquentModel::factory()->suspended()->create(['name' => 'Acme Sales']);

    $owner = UserEloquentModel::query()->findOrFail($team->owner_id);
    $owner->forceFill(['current_team_id' => $team->id])->save();

    return $owner;
}

it('refuses a change to a suspended team and says why', function (): void {
    $owner = ownerOfSuspendedTeam();

    actingAs($owner)
        ->from(route('team.edit'))
        ->patch(route('team.update'), ['name' => 'Acme Support'])
        ->assertRedirect(route('team.edit'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'This workspace is suspended, so nothing in it can be changed.']);

    expect(TeamEloquentModel::query()->findOrFail($owner->current_team_id)->name)->toBe('Acme Sales');
});

it('returns 403 to a request for JSON that would change a suspended team', function (): void {
    $owner = ownerOfSuspendedTeam();

    actingAs($owner)
        ->patchJson(route('team.update'), ['name' => 'Acme Support'])
        ->assertForbidden()
        ->assertJson(['message' => 'This workspace is suspended, so nothing in it can be changed.']);

    expect(TeamEloquentModel::query()->findOrFail($owner->current_team_id)->name)->toBe('Acme Sales');
});

it('still shows a suspended team to its members, marked as suspended', function (): void {
    actingAs(ownerOfSuspendedTeam())
        ->get(route('team.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.currentTeam.is_suspended', true));
});

it('lets a member of a suspended team switch to another team', function (): void {
    $owner = ownerOfSuspendedTeam();
    $otherTeam = TeamEloquentModel::factory()->create();
    CreateTeamUser::fromIds(teamId: $otherTeam->id, userId: $owner->id);

    actingAs($owner)->put(route('current-team.update'), ['team_id' => $otherTeam->id])->assertRedirect();

    expect($owner->fresh()?->current_team_id)->toBe($otherTeam->id);
});

it('lets a member of a suspended team sign out', function (): void {
    actingAs(ownerOfSuspendedTeam())->post(route('logout'))->assertRedirect();

    assertGuest();
});

it("refuses a change from the portal of a suspended team's customer", function (): void {
    $contact = portalContact();
    $ticket = portalTicket($contact);
    TeamEloquentModel::query()->whereKey($contact->team_id)->update(['suspended_at' => now()]);

    actingAs($contact, 'portal')
        ->post(route('portal.tickets.close', $ticket->id))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'This workspace is suspended, so nothing in it can be changed.']);

    expect($ticket->fresh()?->closed_at)->toBeNull();
});
