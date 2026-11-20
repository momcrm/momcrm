<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('shows the invoice as a PDF', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamEloquentModel::query()->findOrFail($user->current_team_id)->update([
        'billing_name' => 'Cebugle Pty Ltd',
        'tax_id_label' => 'ABN',
        'tax_id' => '12 345 678 901',
        'billing_address' => "1 Hay St\nPerth WA 6000",
        'payment_details' => 'BSB 000-000, account 12345678',
        'bpay_biller_code' => '123456',
    ]);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 42]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id]);

    $response = actingAs($user)->get(route('invoices.pdf', $invoice->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=INV-0042.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('downloads the PDF when asked to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 7]);

    actingAs($user)
        ->get(route('invoices.pdf', [$invoice->id, 'download' => 1]))
        ->assertOk()
        ->assertDownload('INV-0007.pdf');
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('invoices.pdf', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
