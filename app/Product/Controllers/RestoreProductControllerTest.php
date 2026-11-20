<?php

declare(strict_types=1);

use App\Product\ProductEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('brings an archived product back', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $product = ProductEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('products.restore', $product->id))->assertSessionHasNoErrors();

    expect($product->fresh()->archived_at)->toBeNull();
});
