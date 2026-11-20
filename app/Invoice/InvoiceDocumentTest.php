<?php

declare(strict_types=1);

use App\Invoice\Actions\FindInvoice;
use App\Invoice\InvoiceDocument;
use App\Invoice\InvoiceEloquentModel;
use App\Team\TeamEloquentModel;

it('heads the invoice by whether the team charges tax under a tax ID', function (?string $label, ?string $taxId, int $taxRate, string $title): void {
    $team = TeamEloquentModel::factory()->create(['tax_id_label' => $label, 'tax_id' => $taxId]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $team->id, 'tax_rate_basis_points' => $taxRate]);

    expect(InvoiceDocument::for(FindInvoice::byIdForTeam($invoice->id, $team->id))->title())->toBe($title);
})->with([
    'ABN, charging GST' => ['ABN', '12 345 678 901', 1000, 'Tax invoice'],
    'VAT number, charging VAT' => ['VAT', 'GB123456789', 2000, 'VAT invoice'],
    'tax ID, charging no tax' => ['ABN', '12 345 678 901', 0, 'Invoice'],
    'no tax ID' => [null, null, 1000, 'Invoice'],
]);

it("puts the team's billing name on the invoice, or its team name without one", function (?string $billingName, string $shown): void {
    $team = TeamEloquentModel::factory()->create(['name' => 'Cebugle', 'billing_name' => $billingName]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $team->id]);

    expect(InvoiceDocument::for(FindInvoice::byIdForTeam($invoice->id, $team->id))->toArray()['from']['name'])->toBe($shown);
})->with([
    'with a billing name' => ['Cebugle Pty Ltd', 'Cebugle Pty Ltd'],
    'without one' => [null, 'Cebugle'],
]);
