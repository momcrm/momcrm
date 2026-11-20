<?php

declare(strict_types=1);

use App\Payment\PaymentEloquentModel;

use function Pest\Laravel\actingAs;

it("shows the customer's receipt as a PDF, but not another customer's", function (): void {
    $contact = portalContact(['is_primary' => true]);
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id]);
    $theirs = PaymentEloquentModel::factory()->create(['team_id' => $contact->team_id]);

    $response = actingAs($contact, 'portal')->get(route('portal.receipts.pdf', $payment->id));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    actingAs($contact, 'portal')->get(route('portal.receipts.pdf', $theirs->id))->assertNotFound();
});
