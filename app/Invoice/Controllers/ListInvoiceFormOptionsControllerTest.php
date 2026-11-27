<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\travelTo;

it('offers customers and their active services with sensible defaults', function (): void {
    travelTo('2026-11-14 09:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    CustomerEloquentModel::factory()->create();
    $rate = RateEloquentModel::factory()->priced(['AUD' => 2500])->create(['team_id' => $user->current_team_id]);
    ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Backup', 'rate_id' => $rate->id, 'billing_period_in_months' => 3]);
    ServiceEloquentModel::factory()->inactive()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    ServiceEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->getJson(route('invoices.form-options', ['customer' => $customer->id]))
        ->assertOk()
        ->assertJsonCount(1, 'customers')
        ->assertJsonPath('customers.0.name', 'Acme IT')
        ->assertJsonCount(1, 'services')
        ->assertJsonPath('services.0.name', 'Backup')
        ->assertJsonPath('services.0.unit_price_in_cents', 7500)
        ->assertJsonPath('defaults', ['issued_on' => '2026-11-14', 'due_on' => '2026-11-28', 'tax_rate_basis_points' => 1000]);
});

it("dues a new invoice by the customer's payment terms, and tells the form each customer's", function (): void {
    travelTo('2026-11-14 09:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamEloquentModel::query()->findOrFail($user->current_team_id)->update(['days_to_pay' => 30]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'days_to_pay' => 7]);
    $other = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $options = actingAs($user)->getJson(route('invoices.form-options', ['customer' => $customer->id]));

    expect($options->json('defaults.due_on'))->toBe('2026-11-21')
        ->and(collect($options->json('customers'))->pluck('days_to_pay', 'id')->sortKeys()->all())
        ->toBe(collect([$customer->id => 7, $other->id => 30])->sortKeys()->all());

    actingAs($user)
        ->getJson(route('invoices.form-options'))
        ->assertJsonPath('defaults.due_on', '2026-12-14');
});

it('needs a signed-in person', function (): void {
    getJson(route('invoices.form-options'))->assertUnauthorized();
});
