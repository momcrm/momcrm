<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a service', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('services.edit', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('services/edit')->where('service.id', $service->id)->where('customer.id', $customer->id));
});

it("returns not found for another team's service", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('services.edit', ServiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
