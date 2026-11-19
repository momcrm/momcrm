<?php

declare(strict_types=1);

use App\Rate\Actions\FindRate;
use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a rate, turning dollars into cents', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('rates.store'), [
            'name' => 'Domain name',
            'description' => '.com.au',
            'price' => '2.50',
            'cost' => '1.25',
            'billing_period_in_months' => 12,
            'prorates_first_month' => '0',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Domain name added.']);

    expect(FindRate::listForTeam($owner->current_team_id)->sole()->toSummary())->toMatchArray([
        'name' => 'Domain name',
        'description' => '.com.au',
        'price_in_cents' => 250,
        'cost_in_cents' => 125,
        'billing_period_in_months' => 12,
        'prorates_first_month' => false,
        'is_archived' => false,
    ]);
});

it('leaves the cost out when it is blank', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('rates.store'), ['name' => 'Support', 'price' => '95', 'cost' => '', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();

    expect(FindRate::listForTeam($owner->current_team_id)->sole()->getCostInCents()->toNullableInteger())->toBeNull();
});

it('checks the fields', function (array $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Taken']);

    actingAs($owner)
        ->post(route('rates.store'), [...['name' => 'Support', 'price' => '95', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'], ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'a name already used, even archived' => [['name' => 'Taken'], 'name'],
    'no price' => [['price' => ''], 'price_in_cents'],
    'a negative price' => [['price' => '-1'], 'price_in_cents'],
    'an odd billing period' => [['billing_period_in_months' => 5], 'billing_period_in_months'],
]);

it('allows a name another team uses', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->create(['name' => 'Support']);

    actingAs($owner)
        ->post(route('rates.store'), ['name' => 'Support', 'price' => '95', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('rates.store'), ['name' => 'Support', 'price' => '95', 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertForbidden();

    expect(RateEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});
