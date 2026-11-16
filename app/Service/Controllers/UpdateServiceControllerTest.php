<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a service', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->put(route('services.update', $service->id), ['name' => 'Managed IT support', 'status' => 'active', 'quantity' => 3, 'unit_price' => '49.95', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01'])
        ->assertRedirectToRoute('customers.services', $customer->id);

    expect($service->fresh())
        ->name->toBe('Managed IT support')
        ->quantity->toBe(3)
        ->unit_price_in_cents->toBe(4995);
});

it("returns not found for another team's service", function (): void {
    $other = ServiceEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('services.update', $other->id), ['name' => 'Managed IT support', 'status' => 'active', 'quantity' => 3, 'unit_price' => '49.95', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01'])
        ->assertNotFound();
});
