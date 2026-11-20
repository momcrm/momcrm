<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("lists a customer's invoices still owing, oldest due first, with what is left on each", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT', 'currency' => 'NZD']);
    $bill = function (string $status, string $dueOn, int $number) use ($user, $customer): InvoiceEloquentModel {
        $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'currency' => 'NZD', 'status' => $status, 'due_on' => $dueOn, 'number' => $number, 'tax_rate_basis_points' => 0]);
        InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

        return $invoice;
    };
    $later = $bill('sent', '2026-12-20', 1);
    $sooner = $bill('sent', '2026-12-01', 2);
    $paidOff = $bill('sent', '2026-11-01', 3);
    $bill('draft', '2026-11-01', 4);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $sooner->id, 'amount_in_cents' => 4000]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $paidOff->id, 'amount_in_cents' => 10000]);

    $response = actingAs($user)
        ->getJson(route('customers.outstanding-invoices', $customer->id))
        ->assertOk()
        ->assertJsonPath('customer.name', 'Acme IT')
        ->assertJsonPath('customer.currency', 'NZD')
        ->assertJsonPath('methods.0', ['value' => 'bank_transfer', 'label' => 'Bank transfer (EFT)']);

    expect($response->json('invoices'))->sequence(
        fn ($invoice) => $invoice->id->toBe($sooner->id)->paid_in_cents->toBe(4000)->balance_in_cents->toBe(6000),
        fn ($invoice) => $invoice->id->toBe($later->id)->balance_in_cents->toBe(10000),
    );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->getJson(route('customers.outstanding-invoices', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
