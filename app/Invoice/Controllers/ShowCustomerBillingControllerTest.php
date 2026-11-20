<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('shows what a customer is due by the day asked, with the due date their terms give', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'days_to_pay' => 7]);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Backup', 'billing_starts_on' => '2026-12-01']);
    $charge = ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Call-out', 'unit_price_in_cents' => 9500, 'charged_on' => '2026-11-20']);

    actingAs($user)
        ->getJson(route('customers.billing', ['customer' => $customer->id, 'date' => '2026-12-02']))
        ->assertOk()
        ->assertJsonPath('date', '2026-12-02')
        ->assertJsonPath('due_on', '2026-12-09')
        ->assertJsonPath('lines', [
            ['key' => "service:{$service->id}:2026-12-01", 'kind' => 'service', 'description' => 'Backup · 1 Dec 2026 – 31 Dec 2026', 'quantity' => 1, 'unit_price_in_cents' => 3000, 'total_in_cents' => 3000],
            ['key' => "charge:{$charge->id}", 'kind' => 'charge', 'description' => 'Call-out', 'quantity' => 1, 'unit_price_in_cents' => 9500, 'total_in_cents' => 9500],
        ]);

    actingAs($user)
        ->getJson(route('customers.billing', ['customer' => $customer->id, 'date' => '2026-11-25']))
        ->assertJsonCount(1, 'lines');
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->getJson(route('customers.billing', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
