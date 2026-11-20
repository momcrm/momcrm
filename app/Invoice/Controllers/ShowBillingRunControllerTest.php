<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows every billable customer of the team with what they are due by the day, by name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create($team);
    $zed = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Zed Co']);
    $acme = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme', 'days_to_pay' => 7]);
    $excluded = CustomerEloquentModel::factory()->create([...$team, 'billing_type' => 'excluded']);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $zed->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    ServiceEloquentModel::factory()->create([...$team, 'customer_id' => $excluded->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    ChargeEloquentModel::factory()->forCustomer($acme)->create(['unit_price_in_cents' => 9500, 'charged_on' => '2026-11-20']);
    $other = CustomerEloquentModel::factory()->create();
    ChargeEloquentModel::factory()->forCustomer($other)->create(['charged_on' => '2026-11-20']);

    actingAs($user)
        ->get(route('invoices.run', ['date' => '2026-12-02']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/run')
            ->where('date', '2026-12-02')
            ->has('customers', 2)
            ->where('customers.0.name', 'Acme')
            ->where('customers.0.due_on', '2026-12-09')
            ->where('customers.0.total_in_cents', 9500)
            ->where('customers.1.name', 'Zed Co')
            ->has('customers.1.lines', 1));
});
