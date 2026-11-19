<?php

declare(strict_types=1);

use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('archives a rate', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Dial-up']);

    actingAs($owner)
        ->post(route('rates.archive', $rate->id))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Dial-up archived.']);

    expect($rate->fresh()->archived_at)->not->toBeNull();
});

it('returns not found for a rate that is already archived', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)->post(route('rates.archive', $rate->id))->assertNotFound();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);

    actingAs($member)->post(route('rates.archive', $rate->id))->assertForbidden();

    expect($rate->fresh()->archived_at)->toBeNull();
});
