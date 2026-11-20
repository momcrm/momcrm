<?php

declare(strict_types=1);

use App\Product\ProductEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a product', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $product = ProductEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('products.archive', $product->id))->assertSessionHasNoErrors();

    expect($product->fresh()->archived_at)->not->toBeNull();
});
