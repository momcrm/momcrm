<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
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
