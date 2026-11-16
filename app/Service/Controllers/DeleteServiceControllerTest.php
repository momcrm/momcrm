<?php

declare(strict_types=1);

use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertModelMissing;

it('deletes a service', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->delete(route('services.destroy', $service->id))
        ->assertRedirectToRoute('customers.services', $service->customer_id);

    assertModelMissing($service);
});

it("returns not found for another team's service", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('services.destroy', ServiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
