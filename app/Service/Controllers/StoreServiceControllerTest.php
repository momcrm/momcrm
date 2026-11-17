<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("adds a service to a customer and returns to the customer's page", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('services.store', $customer->id), ['name' => 'Managed IT support', 'status' => 'active', 'quantity' => 3, 'unit_price' => '49.95', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01'])
        ->assertRedirectToRoute('customers.services', $customer->id);

    expect(ServiceEloquentModel::query()->sole())
        ->team_id->toBe($user->current_team_id)
        ->customer_id->toBe($customer->id)
        ->name->toBe('Managed IT support')
        ->quantity->toBe(3)
        ->unit_price_in_cents->toBe(4995);
});

it('requires the name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('services.store', $customer->id), [])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    expect(ServiceEloquentModel::query()->exists())->toBeFalse();
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('services.store', CustomerEloquentModel::factory()->create()->id), ['name' => 'Managed IT support', 'status' => 'active', 'quantity' => 3, 'unit_price' => '49.95', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01'])
        ->assertNotFound();

    expect(ServiceEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('rejects an end date before the start date', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('services.store', $customer->id), ['name' => 'Backup', 'status' => 'active', 'quantity' => 1, 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01', 'ends_on' => '2026-11-01'])
        ->assertSessionHasErrors(['ends_on' => 'The ends on field must be a date after or equal to starts on.']);
});

it('adds a service with its custom field values, rejecting one of the wrong type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $text = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->create($team);
    $number = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->ofType(CustomFieldTypeEnum::Number)->create([...$team, 'name' => 'Count']);

    Pest\Laravel\actingAs($user)
        ->post(route('services.store', $customer->id), ['name' => 'Managed IT', 'status' => 'active', 'quantity' => 1, 'unit_price' => '10', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01', 'custom_fields' => [$number->id => 'many']])
        ->assertSessionHasErrors(["custom_fields.{$number->id}" => 'The Count field must be a number.']);

    Pest\Laravel\actingAs($user)
        ->post(route('services.store', $customer->id), ['name' => 'Managed IT', 'status' => 'active', 'quantity' => 1, 'unit_price' => '10', 'billing_period_in_months' => 1, 'starts_on' => '2026-12-01', 'custom_fields' => [$text->id => 'North', $number->id => '2']])
        ->assertSessionHasNoErrors();

    expect(ServiceEloquentModel::query()->withoutGlobalScopes()->sole()->custom_fields)->toEqual([$text->id => 'North', $number->id => 2]);
});
