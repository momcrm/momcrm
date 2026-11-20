<?php

declare(strict_types=1);

use App\Payment\PaymentEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's payments, newest first, and not other customers'", function (): void {
    $contact = portalContact(['is_primary' => true]);
    $older = PaymentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'paid_on' => '2026-10-01']);
    $newer = PaymentEloquentModel::factory()->create(['team_id' => $contact->team_id, 'customer_id' => $contact->customer_id, 'paid_on' => '2026-11-01']);
    PaymentEloquentModel::factory()->create(['team_id' => $contact->team_id]);

    actingAs($contact, 'portal')
        ->get(route('portal.receipts.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('portal/receipts')
            ->has('receipts', 2)
            ->where('receipts.0.id', $newer->id)
            ->where('receipts.1.id', $older->id));
});

it('is forbidden to a contact who may not see billing', function (): void {
    actingAs(portalContact(), 'portal')->get(route('portal.receipts.index'))->assertForbidden();
});
