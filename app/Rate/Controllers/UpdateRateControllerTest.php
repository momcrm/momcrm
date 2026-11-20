<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('changes a rate', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Support']);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support, per seat', 'prices' => [['currency' => 'AUD', 'price' => '99.95'], ['currency' => 'NZD', 'price' => '109']], 'billing_period_in_months' => 3, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Support, per seat saved.']);

    expect($rate->fresh())
        ->name->toBe('Support, per seat')
        ->prices->toEqual([
            ['currency' => 'AUD', 'price_in_cents' => 9995, 'cost_in_cents' => null],
            ['currency' => 'NZD', 'price_in_cents' => 10900, 'cost_in_cents' => null],
        ])
        ->billing_period_in_months->toBe(3);
});

it('keeps a price in each currency its customers are billed in', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->priced(['AUD' => 9500, 'USD' => 6200])->create(['team_id' => $owner->current_team_id, 'name' => 'Support']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'currency' => 'USD']);
    ServiceEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id]);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support', 'prices' => [['currency' => 'AUD', 'price' => '95']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasErrors(['prices' => 'Customers on this rate are billed in USD, so it keeps a price in each.']);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support', 'prices' => [['currency' => 'USD', 'price' => '65']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();
});

it('moves a rate under another, but never under itself or a rate under it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $licences = RateEloquentModel::factory()->create([...$team, 'name' => 'Licences']);
    $microsoft = RateEloquentModel::factory()->create([...$team, 'name' => 'Microsoft 365']);
    $standard = RateEloquentModel::factory()->create([...$team, 'parent_id' => $microsoft->id, 'name' => 'Business Standard']);
    $input = fn (string $parentId): array => ['name' => 'Microsoft 365', 'parent_id' => $parentId, 'prices' => [['currency' => 'AUD', 'price' => '1']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'];

    foreach ([$microsoft->id, $standard->id] as $loop) {
        actingAs($owner)
            ->put(route('rates.update', $microsoft->id), $input($loop))
            ->assertSessionHasErrors(['parent_id' => "A rate can't go under itself or a rate under it."]);
    }

    actingAs($owner)->put(route('rates.update', $microsoft->id), $input($licences->id))->assertSessionHasNoErrors();

    expect($microsoft->fresh()->parent_id)->toBe($licences->id);
});

it('keeps its own name without calling it taken', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Support']);

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Support', 'prices' => [['currency' => 'AUD', 'price' => '1']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();
});

it("returns not found for another team's rate", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $rate = RateEloquentModel::factory()->create();

    actingAs($owner)
        ->put(route('rates.update', $rate->id), ['name' => 'Mine now', 'prices' => [['currency' => 'AUD', 'price' => '1']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertNotFound();
});
