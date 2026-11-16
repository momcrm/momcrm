<?php

declare(strict_types=1);

use App\Asset\AssetEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Secret\SecretEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's secrets without their values", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Wi-Fi', 'values' => [['key' => 'password', 'value' => 'never-on-the-page']]]);
    SecretEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Someone else']);

    $response = actingAs($user)->get(route('customers.secrets', $customer->id));

    $response->assertOk()
        ->assertDontSee('never-on-the-page')
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/secrets')
            ->has('secrets', 1)
            ->where('secrets.0.title', 'Wi-Fi')
            ->missing('secrets.0.values')
            ->where('secrets.0.can.reveal', true)
            ->where('workspace.counts.secrets', 1),
        );
});

it('filters by asset and searches titles', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $asset = AssetEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $secret = fn (array $attributes) => SecretEloquentModel::factory()->create([...$attributes, 'team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $secret(['title' => 'Firewall admin', 'asset_id' => $asset->id]);
    $secret(['title' => 'Wi-Fi']);

    actingAs($user)
        ->get(route('customers.secrets', ['customer' => $customer->id, 'asset' => $asset->id]))
        ->assertInertia(fn (Assert $page) => $page->has('secrets', 1)->where('secrets.0.title', 'Firewall admin'));

    actingAs($user)
        ->get(route('customers.secrets', ['customer' => $customer->id, 'search' => 'wi-f']))
        ->assertInertia(fn (Assert $page) => $page->has('secrets', 1)->where('secrets.0.title', 'Wi-Fi'));
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.secrets', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
