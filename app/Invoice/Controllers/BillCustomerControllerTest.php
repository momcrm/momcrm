<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Rate\RateEloquentModel;
use App\Service\ServiceEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('drafts one invoice from the lines picked, priced again, and says which', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme']);
    $rate = RateEloquentModel::factory()->priced(['AUD' => 3000])->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'rate_id' => $rate->id, 'billing_starts_on' => '2026-12-01']);
    $picked = ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Call-out', 'unit_price_in_cents' => 9500, 'charged_on' => '2026-11-20']);
    $left = ChargeEloquentModel::factory()->forCustomer($customer)->create(['charged_on' => '2026-11-20']);

    $response = actingAs($user)
        ->post(route('customers.bill', $customer->id), ['date' => '2026-12-02', 'line_keys' => ["service:{$service->id}:2026-12-01", "charge:{$picked->id}"]])
        ->assertSessionHasNoErrors();

    $invoice = InvoiceEloquentModel::query()->sole();
    $response->assertInertiaFlash('invoiceId', $invoice->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'INV-0001 drafted for Acme.']);

    expect($invoice)->status->toBe('draft')->customer_id->toBe($customer->id)
        ->and($invoice->issued_on->toDateString())->toBe('2026-12-02')
        ->and(InvoiceItemEloquentModel::query()->orderBy('position')->get(['service_id', 'charge_id', 'unit_price_in_cents'])->toArray())->toBe([
            ['service_id' => $service->id, 'charge_id' => null, 'unit_price_in_cents' => 3000],
            ['service_id' => null, 'charge_id' => $picked->id, 'unit_price_in_cents' => 9500],
        ])
        ->and(InvoiceItemEloquentModel::query()->where('charge_id', $left->id)->exists())->toBeFalse();
});

it('refuses lines that are billed or never were', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $charge = ChargeEloquentModel::factory()->forCustomer($customer)->create(['charged_on' => '2026-11-20']);
    actingAs($user)->post(route('customers.bill', $customer->id), ['date' => '2026-12-02', 'line_keys' => ["charge:{$charge->id}"]]);

    actingAs($user)
        ->post(route('customers.bill', $customer->id), ['date' => '2026-12-02', 'line_keys' => ["charge:{$charge->id}"]])
        ->assertSessionHasErrors(['line_keys' => 'Some of those lines were billed or changed since. Look again.']);

    actingAs($user)
        ->post(route('customers.bill', $customer->id), ['date' => '2026-12-02', 'line_keys' => []])
        ->assertSessionHasErrors('line_keys');

    expect(InvoiceEloquentModel::query()->count())->toBe(1);
});

it('refuses someone an access rule keeps from creating invoices', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create(['onboarded_at' => now()]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $owner->current_team_id]);
    AccessRuleEloquentModel::factory()->create([
        'team_id' => $owner->current_team_id,
        'name' => 'Only admins bill',
        'effect' => 'deny',
        'resources' => ['invoice'],
        'actions' => ['create'],
        'conditions' => [['attribute' => 'subject.team_role', 'operator' => 'equals', 'value' => 'member']],
    ]);

    actingAs($member)
        ->post(route('customers.bill', $customer->id), ['date' => '2026-12-02', 'line_keys' => ['charge:x']])
        ->assertForbidden();
});
