<?php

declare(strict_types=1);

use App\Location\LocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a location', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $location = LocationEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('locations.destroy', $location->id))
        ->assertRedirectToRoute('customers.locations', $location->customer_id);

    assertModelMissing($location);
});

it("returns not found for another team's location", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('locations.destroy', LocationEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
