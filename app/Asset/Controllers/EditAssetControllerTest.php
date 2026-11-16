<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Secret\SecretEloquentModel;
use App\User\UserEloquentModel;
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
