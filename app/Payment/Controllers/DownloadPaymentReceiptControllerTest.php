<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Payment\Actions\FindPayment;
use App\Payment\PaymentDocument;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("shows a payment's receipt as a PDF", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 42]);

    $response = actingAs($user)->get(route('payments.receipt', $payment->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=PAY-0042.pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('downloads the receipt when asked to', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 7]);

    actingAs($user)
        ->get(route('payments.receipt', [$payment->id, 'download' => 1]))
        ->assertOk()
        ->assertDownload('PAY-0007.pdf');
});

it('prints the invoices paid, what is kept as credit and the credit the customer has', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'amount_in_cents' => 15000, 'method' => 'cheque', 'reference' => 'CHQ 0042']);
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $payment->customer_id, 'number' => 3]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id, 'amount_in_cents' => 10000]);

    $html = view('payments.receipt', ['document' => PaymentDocument::for(FindPayment::byIdForTeam($payment->id, $user->current_team_id))])->render();

    expect($html)
        ->toContain('RECEIPT')
        ->toContain('Cheque')
        ->toContain('CHQ 0042')
        ->toContain('Invoice INV-0003')
        ->toContain('A$100.00')
        ->toContain('Kept as credit')
        ->toContain('You have A$50.00 in credit with us.');
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('payments.receipt', PaymentEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
