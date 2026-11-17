<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\Secret\SecretEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a asset', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('assets.edit', $asset->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('assets/edit')->where('asset.id', $asset->id)->where('customer.id', $customer->id));
});

it("returns not found for another team's asset", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('assets.edit', AssetEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it("lists the asset's secrets, without their values", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'asset_id' => $asset->id, 'title' => 'Admin login']);
    SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Wi-Fi']);

    actingAs($user)
        ->get(route('assets.edit', $asset->id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('secrets', 1)
            ->where('secrets.0.title', 'Admin login')
            ->missing('secrets.0.values'),
        );
});

it('sends the asset its custom field values, the names they link to, and what links to it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $supplier = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Asset)->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $asset = AssetEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'The asset', 'custom_fields' => [$supplier->id => $customer->id]]);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Asset)->create(['name' => 'Kept at']);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$link->id => $asset->id]]);

    Pest\Laravel\actingAs($user)
        ->get(route('assets.edit', $asset->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("asset.custom_fields.{$supplier->id}", $customer->id)
            ->where('customFieldLabels', [$customer->id => 'Acme'])
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Kept at')
            ->where('linkedRecords.0.records.0.title', 'Van'),
        );
});
