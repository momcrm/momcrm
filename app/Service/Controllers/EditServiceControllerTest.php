<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Rate\RateEloquentModel;
use App\Record\RecordEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a service, with its rate, what it costs and the rates to choose from', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $rate = RateEloquentModel::factory()->archived()->create([...$team, 'name' => 'Old support', 'price_in_cents' => 1000, 'billing_period_in_months' => 3]);
    RateEloquentModel::factory()->archived()->create([...$team, 'name' => 'Another old one']);
    RateEloquentModel::factory()->create([...$team, 'name' => 'Support']);
    $service = ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'quantity' => 2]);

    actingAs($user)
        ->get(route('services.edit', $service->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('services/edit')
            ->where('service.id', $service->id)
            ->where('service.rate.name', 'Old support')
            ->where('service.effective_billing_period_in_months', 3)
            ->where('service.unit_price_in_cents', 3000)
            ->where('service.rental_in_cents', 6000)
            ->where('customer.id', $customer->id)
            ->where('rates.0.name', 'Old support')
            ->where('rates.1.name', 'Support')
            ->has('rates', 2)
            ->where('billingPeriods', [1, 3, 6, 12, 24, 36])
            ->where('can.archive', true));
});

it("returns not found for another team's service", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('services.edit', ServiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('sends the service its custom field values, the names they link to, and what links to it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $supplier = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Service)->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $service = ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'The service', 'custom_fields' => [$supplier->id => $customer->id]]);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Service)->create(['name' => 'Kept at']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$link->id => $service->id]]);

    Pest\Laravel\actingAs($user)
        ->get(route('services.edit', $service->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("service.custom_fields.{$supplier->id}", $customer->id)
            ->where('customFieldLabels', [$customer->id => 'Acme'])
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Kept at')
            ->where('linkedRecords.0.records.0.title', 'Van'),
        );
});
