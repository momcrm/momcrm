<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('changes a charge waiting for an invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $charge = ChargeEloquentModel::factory()->forCustomer(CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]))->create();

    actingAs($user)
        ->put(route('charges.update', $charge->id), ['description' => 'Call-out', 'quantity' => '3', 'unit_price' => '50', 'charged_on' => '2026-11-03'])
        ->assertSessionHasNoErrors();

    expect($charge->fresh())->description->toBe('Call-out')->quantity->toBe(3)->unit_price_in_cents->toBe(5000);
});

it('leaves a charge alone once it is on an invoice that is not voided', function (string $status, bool $isLocked): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $charge = ChargeEloquentModel::factory()->forCustomer($customer)->create(['description' => 'Old']);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 7, 'status' => $status]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'charge_id' => $charge->id]);

    $response = actingAs($user)->put(route('charges.update', $charge->id), ['description' => 'New', 'quantity' => '1', 'unit_price' => '50', 'charged_on' => '2026-11-03']);

    $isLocked
        ? $response->assertSessionHasErrors(['charge' => 'It is on INV-0007. Change it there, or void that invoice first.'])
        : $response->assertSessionHasNoErrors();
    expect($charge->fresh()->description)->toBe($isLocked ? 'Old' : 'New');
})->with([
    'draft' => ['draft', true],
    'sent' => ['sent', true],
    'void' => ['void', false],
]);
