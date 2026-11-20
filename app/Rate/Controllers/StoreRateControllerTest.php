<?php

declare(strict_types=1);

use App\Rate\Actions\FindRate;
use App\Rate\RateEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a rate priced in several currencies, turning dollars into cents', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('rates.store'), [
            'name' => 'Domain name',
            'description' => '.com.au',
            'prices' => [
                ['currency' => 'AUD', 'price' => '2.50', 'cost' => '1.25'],
                ['currency' => 'usd', 'price' => '1.70', 'cost' => ''],
            ],
            'billing_period_in_months' => 12,
            'prorates_first_month' => '0',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Domain name added.']);

    expect(FindRate::listForTeam($owner->current_team_id)->sole()->toSummary())->toMatchArray([
        'name' => 'Domain name',
        'description' => '.com.au',
        'prices' => [
            ['currency' => 'AUD', 'price_in_cents' => 250, 'cost_in_cents' => 125],
            ['currency' => 'USD', 'price_in_cents' => 170, 'cost_in_cents' => null],
        ],
        'billing_period_in_months' => 12,
        'prorates_first_month' => false,
        'is_archived' => false,
    ]);
});

it('checks the fields', function (array $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Taken']);

    actingAs($owner)
        ->post(route('rates.store'), [...['name' => 'Support', 'prices' => [['currency' => 'AUD', 'price' => '95']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'], ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'a name already used, even archived' => [['name' => 'Taken'], 'name'],
    'no prices' => [['prices' => []], 'prices'],
    'no price' => [['prices' => [['currency' => 'AUD', 'price' => '']]], 'prices.0.price_in_cents'],
    'a negative price' => [['prices' => [['currency' => 'AUD', 'price' => '-1']]], 'prices.0.price_in_cents'],
    'a currency we do not bill in' => [['prices' => [['currency' => 'XYZ', 'price' => '1']]], 'prices.0.currency'],
    'two prices in one currency' => [['prices' => [['currency' => 'AUD', 'price' => '1'], ['currency' => 'AUD', 'price' => '2']]], 'prices.1.currency'],
    'an odd billing period' => [['billing_period_in_months' => 5], 'billing_period_in_months'],
]);

it('adds a rate under another, where its name need only differ from the rates beside it', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $owner->current_team_id];
    $microsoft = RateEloquentModel::factory()->create([...$team, 'name' => 'Microsoft 365']);
    $google = RateEloquentModel::factory()->create([...$team, 'name' => 'Google Workspace']);
    RateEloquentModel::factory()->create([...$team, 'parent_id' => $google->id, 'name' => 'Business Standard']);
    $input = ['name' => 'Business Standard', 'prices' => [['currency' => 'AUD', 'price' => '18.70']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'];

    actingAs($owner)->post(route('rates.store'), [...$input, 'parent_id' => $microsoft->id])->assertSessionHasNoErrors();
    actingAs($owner)->post(route('rates.store'), [...$input, 'parent_id' => $microsoft->id])->assertSessionHasErrors(['name' => 'There is already a rate with that name here, or one in the archive.']);

    expect(RateEloquentModel::query()->where('parent_id', $microsoft->id)->sole()->name)->toBe('Business Standard');
});

it('puts a rate only under a live rate of the team', function (Closure $parent, string $message): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('rates.store'), ['name' => 'Support', 'parent_id' => $parent($owner->current_team_id), 'prices' => [['currency' => 'AUD', 'price' => '95']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasErrors(['parent_id' => $message]);
})->with([
    "another team's rate" => [fn (): string => RateEloquentModel::factory()->create()->id, 'Choose one of your rates.'],
    'an archived rate' => [fn (string $team): string => RateEloquentModel::factory()->archived()->create(['team_id' => $team, 'name' => 'Old'])->id, 'Old is archived. Choose a rate that is still offered.'],
]);

it('allows a name another team uses', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    RateEloquentModel::factory()->create(['name' => 'Support']);

    actingAs($owner)
        ->post(route('rates.store'), ['name' => 'Support', 'prices' => [['currency' => 'AUD', 'price' => '95']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertSessionHasNoErrors();
});

it('forbids members', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();

    actingAs($member)
        ->post(route('rates.store'), ['name' => 'Support', 'prices' => [['currency' => 'AUD', 'price' => '95']], 'billing_period_in_months' => 1, 'prorates_first_month' => '1'])
        ->assertForbidden();

    expect(RateEloquentModel::query()->withoutGlobalScopes()->count())->toBe(0);
});
