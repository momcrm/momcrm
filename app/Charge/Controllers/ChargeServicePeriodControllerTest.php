<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function single_billing_setup(): array
{
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'name' => 'Backup', 'billing_starts_on' => '2026-11-01']);

    return [$user, $customer, $service];
}

it("bills a service's next period as a waiting line, then the one after", function (): void {
    [$user, $customer, $service] = single_billing_setup();

    actingAs($user)
        ->post(route('customers.billing.service-period.store', $customer->id), ['service_id' => $service->id, 'month' => '2026-12'])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Backup · 1 Nov 2026 – 30 Nov 2026 is waiting to be invoiced.']);

    actingAs($user)
        ->post(route('customers.billing.service-period.store', $customer->id), ['service_id' => $service->id, 'month' => '2026-12'])
        ->assertSessionHasNoErrors();

    expect(ChargeEloquentModel::query()->orderBy('rental_starts_on')->get()->map(fn (ChargeEloquentModel $charge): array => [
        $charge->service_id, $charge->unit_price_in_cents, $charge->rental_starts_on->toDateString(), $charge->rental_ends_on->toDateString(),
    ])->all())->toBe([
        [$service->id, 3000, '2026-11-01', '2026-11-30'],
        [$service->id, 3000, '2026-12-01', '2026-12-31'],
    ]);

    actingAs($user)
        ->post(route('customers.billing.service-period.store', $customer->id), ['service_id' => $service->id, 'month' => '2026-12'])
        ->assertSessionHasErrors(['service_id' => 'This service is already invoiced, or waiting to be, for every period starting by then.']);
});

it("refuses another customer's service and a month before the service starts", function (): void {
    [$user, $customer, $service] = single_billing_setup();
    $other = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('customers.billing.service-period.store', $customer->id), ['service_id' => $other->id, 'month' => '2026-12'])
        ->assertSessionHasErrors(['service_id' => 'Choose one of their services.']);

    actingAs($user)
        ->post(route('customers.billing.service-period.store', $customer->id), ['service_id' => $service->id, 'month' => '2026-10'])
        ->assertSessionHasErrors('service_id');

    expect(ChargeEloquentModel::query()->count())->toBe(0);
});
