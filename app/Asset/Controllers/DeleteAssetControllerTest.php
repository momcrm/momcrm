<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a asset', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('assets.destroy', $asset->id))
        ->assertRedirectToRoute('customers.show', $asset->customer_id);

    assertModelMissing($asset);
});

it("returns not found for another team's asset", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('assets.destroy', AssetEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
