<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 4]);

    actingAs($user)
        ->put(route('customers.update', $customer->id), ['name' => 'Renamed', 'status' => 'inactive', 'number' => 99])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect($customer->fresh())
        ->name->toBe('Renamed')
        ->status->toBe('inactive')
        ->number->toBe(4);
});

it("returns not found for another team's customer", function (): void {
    $other = CustomerEloquentModel::factory()->create(['name' => 'Theirs']);

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('customers.update', $other->id), ['name' => 'Mine', 'status' => 'active'])
        ->assertNotFound();

    expect($other->fresh()->name)->toBe('Theirs');
});
