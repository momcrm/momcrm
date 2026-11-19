<?php

declare(strict_types=1);

use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows an owner the team's rates by name, archived ones included", function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'name' => 'Website hosting', 'price_in_cents' => 2500]);
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
            ->where('rates.1.price_in_cents', 2500)
            ->where('billingPeriods', [1, 3, 6, 12, 24, 36]),
        );
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)->get(route('rates.index'))->assertForbidden();
});
