<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows the customer's statement for the period", function (): void {
    $contact = portalContact(['is_primary' => true]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'sent', 'issued_on' => '2026-11-02']);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $contact->team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

    actingAs($contact, 'portal')
        ->get(route('portal.statement', ['from' => '2026-11-01', 'to' => '2026-11-30']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/statement')
            ->where('statement.customer.id', $contact->customer_id)
            ->has('statement.entries', 1)
            ->where('statement.closing_balance_in_cents', 11000)
            ->where('canSeeInvoices', true));
});

it('is forbidden to a contact who may not see billing', function (): void {
    actingAs(portalContact(), 'portal')->get(route('portal.statement'))->assertForbidden();
});
