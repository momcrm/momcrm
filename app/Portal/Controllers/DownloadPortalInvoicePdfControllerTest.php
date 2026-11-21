<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;

use function Pest\Laravel\actingAs;

it("shows the customer's invoice as a PDF", function (): void {
    $contact = portalContact(['receives_invoices' => true]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'status' => 'sent', 'number' => 42]);

    $response = actingAs($contact, 'portal')->get(route('invoices.pdf', $invoice->id));

    $response->assertOk()->assertHeader('Content-Disposition', 'inline; filename=INV-0042.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it("doesn't show a draft, another customer's invoice, or any to a contact who may not see billing", function (): void {
    $contact = portalContact(['is_primary' => true]);
    $draft = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id]);
    $theirs = InvoiceEloquentModel::factory()->create(['team_id' => $contact->team_id, 'status' => 'sent']);

    actingAs($contact, 'portal')->get(route('invoices.pdf', $draft->id))->assertNotFound();
    actingAs($contact, 'portal')->get(route('invoices.pdf', $theirs->id))->assertNotFound();

    $colleague = portalContact();
    $sent = InvoiceEloquentModel::factory()->create(['team_id' => $colleague->team_id, 'customer_id' => $colleague->customer_id, 'status' => 'sent']);

    actingAs($colleague, 'portal')->get(route('invoices.pdf', $sent->id))->assertNotFound();
});
