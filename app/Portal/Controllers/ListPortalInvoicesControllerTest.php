<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's sent and paid invoices, never drafts, voided ones or other customers'", function (): void {
    $contact = portalContact(['is_primary' => true]);
    $sent = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'sent', 'number' => 3]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $contact->team_id, 'invoice_id' => $sent->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);
    InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'draft']);
    InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'void']);
    InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'status' => 'sent']);

    actingAs($contact, 'portal')
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/invoices')
            ->has('invoices', 1)
            ->where('invoices.0.id', $sent->id)
            ->where('invoices.0.reference', 'INV-0003')
            ->where('invoices.0.total_in_cents', 11000)
            ->where('invoices.0.balance_in_cents', 11000)
            ->missing('invoices.0.notes'));
});

it('is forbidden to a contact who is neither primary nor gets invoices', function (): void {
    actingAs(portalContact(), 'portal')->get(route('invoices.index'))->assertForbidden();
});
