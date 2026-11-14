<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a asset', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->put(route('assets.update', $asset->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertRedirectToRoute('customers.show', $customer->id);

    expect($asset->fresh())
        ->name->toBe('Reception PC')
        ->serial_number->toBe('SN-1')
        ->status->toBe('active');
});

it("returns not found for another team's asset", function (): void {
    $other = AssetEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('assets.update', $other->id), ['name' => 'Reception PC', 'serial_number' => 'SN-1', 'status' => 'active'])
        ->assertNotFound();
});
