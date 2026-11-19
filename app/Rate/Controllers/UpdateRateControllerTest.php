<?php

declare(strict_types=1);

use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('changes a rate', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Support']);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support, per seat', 'price' => '99.95', 'billing_period_in_months' => 3, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Support, per seat saved.']);

    expect($rate->fresh())
        ->name->toBe('Support, per seat')
        ->price_in_cents->toBe(9995)
        ->billing_period_in_months->toBe(3);
});

it('keeps its own name without calling it taken', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Support']);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support', 'price' => '1', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();
});

it("returns not found for another team's rate", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create();

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Mine now', 'price' => '1', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertNotFound();
});
