<?php

declare(strict_types=1);

use App\TeamInvitation\TeamInvitationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('revokes a pending invitation', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invitation = TeamInvitationEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'email' => 'sam@acme.test']);

    actingAs($owner)
        ->delete(route('team.invitations.destroy', $invitation->id))
        ->assertRedirectToRoute('team.edit');

    assertModelMissing($invitation);
});

it("returns not found for another team's invitation", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $otherInvitation = TeamInvitationEloquentModel::factory()->create();

    actingAs($owner)
        ->delete(route('team.invitations.destroy', $otherInvitation->id))
        ->assertNotFound();

    expect(TeamInvitationEloquentModel::query()->withoutGlobalScopes()->whereKey($otherInvitation->id)->exists())->toBeTrue();
});
