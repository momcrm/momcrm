<?php

declare(strict_types=1);

use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows an owner the team's rates by name, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->priced(['AUD' => 2500])->create(['team_id' => $owner->current_team_id, 'name' => 'Website hosting']);
    RateEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Dial-up']);
    RateEloquentModel::factory()->create(['name' => "Another team's rate"]);

    actingAs($owner)
        ->get(route('rates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/rates')
            ->has('rates', 2)
            ->where('rates.0.name', 'Dial-up')
            ->where('rates.0.is_archived', true)
            ->where('rates.1.name', 'Website hosting')
            ->where('rates.1.prices', [['currency' => 'AUD', 'price_in_cents' => 2500, 'cost_in_cents' => null]])
            ->where('currencies.0', ['value' => 'AUD', 'label' => 'AUD · Australian dollar'])
            ->where('billingPeriods', [1, 3, 6, 12, 24, 36]),
        );
});

it('lists rates as a tree, each followed by the rates under it, with its depth and path', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $microsoft = RateEloquentModel::factory()->create([...$team, 'name' => 'Microsoft 365']);
    RateEloquentModel::factory()->create([...$team, 'name' => 'Backup']);
    $standard = RateEloquentModel::factory()->create([...$team, 'parent_id' => $microsoft->id, 'name' => 'Business Standard']);
    RateEloquentModel::factory()->create([...$team, 'parent_id' => $standard->id, 'name' => 'Annual']);
    RateEloquentModel::factory()->create([...$team, 'parent_id' => $microsoft->id, 'name' => 'Business Basic']);

    actingAs($owner)
        ->get(route('rates.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rates', fn ($rates): bool => collect($rates)->map(fn (array $rate): array => [$rate['depth'], $rate['path']])->all() === [
                [0, 'Backup'],
                [0, 'Microsoft 365'],
                [1, 'Microsoft 365 › Business Basic'],
                [1, 'Microsoft 365 › Business Standard'],
                [2, 'Microsoft 365 › Business Standard › Annual'],
            ]),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('rates.index'))->assertForbidden();
});
