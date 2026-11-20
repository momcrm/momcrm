<?php

declare(strict_types=1);

use App\Product\ProductEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it("lists the team's products by name, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ProductEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Laptop setup']);
    ProductEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Call-out']);
    ProductEloquentModel::factory()->create(['name' => 'Another team']);

    actingAs($owner)
        ->get(route('products.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/products')
            ->has('products', 2)
            ->where('products.0.name', 'Call-out')
            ->where('products.0.is_archived', true)
            ->where('products.1.name', 'Laptop setup')
            ->has('currencies'));
});
