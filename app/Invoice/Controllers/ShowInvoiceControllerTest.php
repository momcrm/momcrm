<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows an invoice with its lines and totals', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'tax_rate_basis_points' => 1000]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 2, 'unit_price_in_cents' => 2500]);

    actingAs($user)
        ->get(route('invoices.show', $invoice->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/show')
            ->has('items', 1)
            ->where('items.0.line_total_in_cents', 5000)
            ->where('totals', ['subtotal_in_cents' => 5000, 'tax_in_cents' => 500, 'total_in_cents' => 5500]),
        );
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('invoices.show', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
