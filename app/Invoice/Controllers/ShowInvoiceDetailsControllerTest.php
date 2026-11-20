<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('sends the invoice dialog the invoice, its lines, totals, customer and team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $invoice = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'tax_rate_basis_points' => 1000]);
    InvoiceItemEloquentModel::factory()->create([...$team, 'invoice_id' => $invoice->id, 'quantity' => 2, 'unit_price_in_cents' => 2500]);

    actingAs($user)
        ->getJson(route('invoices.details', $invoice->id))
        ->assertOk()
        ->assertJsonPath('invoice.id', $invoice->id)
        ->assertJsonCount(1, 'items')
        ->assertJsonPath('totals', ['subtotal_in_cents' => 5000, 'tax_in_cents' => 500, 'total_in_cents' => 5500])
        ->assertJsonPath('customer.name', 'Acme')
        ->assertJsonPath('from.name', $user->currentTeam->name);
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->getJson(route('invoices.details', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
