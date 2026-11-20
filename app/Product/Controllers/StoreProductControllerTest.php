<?php

declare(strict_types=1);

use App\Product\Actions\FindProduct;
use App\Product\ProductEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('adds a product priced in several currencies, turning dollars into cents', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($owner)
        ->post(route('products.store'), [
            'name' => 'Laptop setup',
            'description' => 'Imaging and delivery',
            'prices' => [['currency' => 'AUD', 'price' => '350', 'cost' => '120.50'], ['currency' => 'nzd', 'price' => '380', 'cost' => '']],
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Laptop setup added.']);

    expect(FindProduct::listForTeam($owner->current_team_id)->sole()->toSummary())->toMatchArray([
        'name' => 'Laptop setup',
        'description' => 'Imaging and delivery',
        'prices' => [
            ['currency' => 'AUD', 'price_in_cents' => 35000, 'cost_in_cents' => 12050],
            ['currency' => 'NZD', 'price_in_cents' => 38000, 'cost_in_cents' => null],
        ],
        'is_archived' => false,
    ]);
});

it('checks the fields', function (array $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    ProductEloquentModel::factory()->archived()->create(['team_id' => $owner->current_team_id, 'name' => 'Taken']);

    actingAs($owner)
        ->post(route('products.store'), [...['name' => 'Call-out', 'prices' => [['currency' => 'AUD', 'price' => '95']]], ...$input])
        ->assertSessionHasErrors($field);
})->with([
    'a name already used, even archived' => [['name' => 'Taken'], 'name'],
    'no prices' => [['prices' => []], 'prices'],
    'a negative price' => [['prices' => [['currency' => 'AUD', 'price' => '-1']]], 'prices.0.price_in_cents'],
    'two prices in one currency' => [['prices' => [['currency' => 'AUD', 'price' => '1'], ['currency' => 'AUD', 'price' => '2']]], 'prices.1.currency'],
]);

it('leaves products to owners and admins', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();

    actingAs($member)->post(route('products.store'), ['name' => 'Call-out', 'prices' => [['currency' => 'AUD', 'price' => '95']]])->assertForbidden();
    actingAs($member)->get(route('products.index'))->assertForbidden();
});
