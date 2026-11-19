<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('saves changes to a service, leaving its rate and status to their own actions', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'short_name' => 'old']);
    $rate = $service->rate_id;

    actingAs($user)
        ->from(route('services.edit', $service->id))
        ->put(route('services.update', $service->id), [
            'name' => 'Managed IT support',
            'short_name' => '',
            'quantity' => 3,
            'billing_starts_on' => '2026-12-01',
            'rate_id' => RateEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
            'status' => 'inactive',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('services.edit', $service->id);

    expect($service->fresh())
        ->name->toBe('Managed IT support')
        ->short_name->toBe('managed-it-support')
        ->quantity->toBe(3)
        ->rate_id->toBe($rate)
        ->status->toBe('active');
});

it('keeps its own short name without calling it taken', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'short_name' => 'example.com']);

    actingAs($user)
        ->put(route('services.update', $service->id), ['name' => 'Domain', 'short_name' => 'example.com', 'quantity' => 1, 'billing_starts_on' => '2026-12-01'])
        ->assertSessionHasNoErrors();
});

it("returns not found for another team's service", function (): void {
    $other = ServiceEloquentModel::factory()->create();

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('services.update', $other->id), ['name' => 'Managed IT support', 'quantity' => 3, 'billing_starts_on' => '2026-12-01'])
        ->assertNotFound();
});

it('changes a service custom field value, keeping the ones not sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->create($team);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->create($team);
    $service = ServiceEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id, 'custom_fields' => [$kept->id => 'kept']]);

    actingAs($user)
        ->put(route('services.update', $service->id), ['name' => 'Managed IT', 'quantity' => 1, 'billing_starts_on' => '2026-12-01', 'custom_fields' => [$set->id => 'new']])
        ->assertSessionHasNoErrors();

    expect($service->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => 'new']);
});
