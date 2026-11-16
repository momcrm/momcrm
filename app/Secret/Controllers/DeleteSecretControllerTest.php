<?php

declare(strict_types=1);

use App\Secret\SecretEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a secret', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('secrets.destroy', $secret->id))
        ->assertRedirectToRoute('customers.secrets', $secret->customer_id);

    assertModelMissing($secret);
});

it('leaves deleting secrets to owners and admins by default', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $secret = SecretEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->delete(route('secrets.destroy', $secret->id))->assertForbidden();

    expect($secret->fresh())->not->toBeNull();
});

it("returns not found for another team's secret", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('secrets.destroy', SecretEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
