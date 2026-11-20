<?php

declare(strict_types=1);

use App\Product\ProductEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("changes a product's name and prices", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $product = ProductEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->put(route('products.update', $product->id), ['name' => 'Call-out', 'prices' => [['currency' => 'AUD', 'price' => '120']]])
        ->assertSessionHasNoErrors();

    expect($product->fresh())->name->toBe('Call-out')
        ->and($product->fresh()->prices)->toEqual([['currency' => 'AUD', 'price_in_cents' => 12000, 'cost_in_cents' => null]]);
});

it("returns not found for another team's product", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('products.update', ProductEloquentModel::factory()->create()->id), ['name' => 'Mine', 'prices' => [['currency' => 'AUD', 'price' => '1']]])
        ->assertNotFound();
});
