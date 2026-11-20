<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('offers customers and their active services with sensible defaults', function (): void {
    travelTo('2026-11-14 09:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 2500])->create(['team_id' => $user->current_team_id]);
    ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'name' => 'Backup', 'rate_id' => $rate->id, 'billing_period_in_months' => 3]);
    ServiceEloquentModel::factory()->inactive()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    ServiceEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('invoices.create', ['customer' => $customer->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/create')
            ->where('customerId', $customer->id)
            ->has('services', 1)
            ->where('services.0.name', 'Backup')
            ->where('services.0.unit_price_in_cents', 7500)
            ->where('defaults', ['issued_on' => '2026-11-14', 'due_on' => '2026-11-28', 'tax_rate_basis_points' => 1000]),
        );
});
