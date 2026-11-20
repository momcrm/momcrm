<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Product\ProductEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it("charges a customer for a product, at its price in the customer's currency", function (): void {
    travelTo('2026-11-20 10:00:00');
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id, TeamRoleEnum::Member)->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'currency' => 'NZD']);
    $product = ProductEloquentModel::factory()->priced(['AUD' => 35000, 'NZD' => 38000])->create(['team_id' => $owner->current_team_id, 'name' => 'Laptop setup']);

    actingAs($member)
        ->post(route('charges.store'), ['customer_id' => $customer->id, 'product_id' => $product->id, 'quantity' => '2'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Laptop setup charged. It goes on their next invoice.']);

    expect(ChargeEloquentModel::query()->sole())
        ->customer_id->toBe($customer->id)
        ->product_id->toBe($product->id)
        ->description->toBe('Laptop setup')
        ->quantity->toBe(2)
        ->unit_price_in_cents->toBe(38000)
        ->currency->toBe('NZD')
        ->and(ChargeEloquentModel::query()->sole()->charged_on->toDateString())->toBe('2026-11-20');
});

it('charges something typed in, overriding what a product says', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    $product = ProductEloquentModel::factory()->priced(['AUD' => 9500])->create(['team_id' => $owner->current_team_id]);

    actingAs($owner)
        ->post(route('charges.store'), ['customer_id' => $customer->id, 'product_id' => $product->id, 'description' => 'Call-out, after hours', 'unit_price' => '142.50', 'quantity' => '1', 'charged_on' => '2026-11-03'])
        ->assertSessionHasNoErrors();

    expect(ChargeEloquentModel::query()->sole())
        ->description->toBe('Call-out, after hours')
        ->unit_price_in_cents->toBe(14250)
        ->and(ChargeEloquentModel::query()->sole()->charged_on->toDateString())->toBe('2026-11-03');
});

it('checks the fields', function (Closure $input, string $field): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id, 'currency' => 'USD']);

    actingAs($owner)
        ->post(route('charges.store'), [...['customer_id' => $customer->id, 'description' => 'Call-out', 'unit_price' => '95'], ...$input($owner->current_team_id)])
        ->assertSessionHasErrors($field);
})->with([
    'no price, and a product not sold in their currency' => [fn (string $teamId): array => ['description' => '', 'unit_price' => '', 'product_id' => ProductEloquentModel::factory()->priced(['AUD' => 100])->create(['team_id' => $teamId])->id], 'unit_price_in_cents'],
    "another team's customer" => [fn (): array => ['customer_id' => CustomerEloquentModel::factory()->create()->id], 'customer_id'],
    "another team's product" => [fn (): array => ['product_id' => ProductEloquentModel::factory()->create()->id], 'product_id'],
    'no description' => [fn (): array => ['description' => ''], 'description'],
]);
