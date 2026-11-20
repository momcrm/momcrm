<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("shows the period Single Service Billing would bill for a month, or why there's none", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Backup', 'billing_starts_on' => '2026-12-01']);
    $unrated = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => null]);

    actingAs($user)
        ->getJson(route('customers.billing.service-period', ['customer' => $customer->id, 'service' => $service->id, 'month' => '2026-12']))
        ->assertJsonPath('line.description', 'Backup · 1 Dec 2026 – 31 Dec 2026')
        ->assertJsonPath('line.total_in_cents', 3000)
        ->assertJsonPath('reason', null);

    actingAs($user)
        ->getJson(route('customers.billing.service-period', ['customer' => $customer->id, 'service' => $unrated->id, 'month' => '2026-12']))
        ->assertJsonPath('line', null)
        ->assertJsonPath('reason', 'This service isn’t on a rate, so it has nothing to bill.');
});
