<?php

declare(strict_types=1);

use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('restores an archived rate', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Dial-up']);

    actingAs($owner)
        ->post(route('rates.restore', $rate->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Dial-up restored.']);

    expect($rate->fresh()->archived_at)->toBeNull();
});

it('returns not found for a rate that is not archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('rates.restore', $rate->id))->assertNotFound();
});
