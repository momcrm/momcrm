<?php

declare(strict_types=1);

use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("changes a payment's date, method, reference and notes", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 3, 'amount_in_cents' => 5000]);

    actingAs($user)
        ->from(route('payments.index'))
        ->put(route('payments.update', $payment->id), [
            'paid_on' => '2026-11-02',
            'method' => 'cheque',
            'reference' => 'CHQ 0042',
            'notes' => 'Posted late.',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('payments.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'PAY-0003 saved.']);

    expect($payment->fresh())
        ->paid_on->toDateString()->toBe('2026-11-02')
        ->method->toBe('cheque')
        ->reference->toBe('CHQ 0042')
        ->notes->toBe('Posted late.')
        ->amount_in_cents->toBe(5000);
});

it('raises the amount, leaving the rest as credit, but not below what is applied', function (string $amount, ?string $error, int $stored): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'amount_in_cents' => 5000]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'payment_id' => $payment->id, 'amount_in_cents' => 4000]);

    $response = actingAs($user)->put(route('payments.update', $payment->id), ['amount' => $amount, 'paid_on' => '2026-11-02', 'method' => 'cash']);

    $error === null ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors(['amount_in_cents' => $error]);
    expect($payment->fresh()->amount_in_cents)->toBe($stored);
})->with([
    'raised' => ['80.00', null, 8000],
    'down to what is applied' => ['40.00', null, 4000],
    'below what is applied' => ['39.99', "A$40.00 of it has been applied to invoices, so it can't be less. Take it off an invoice first.", 5000],
]);

it('asks for the date and a known method', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->put(route('payments.update', $payment->id), ['paid_on' => '', 'method' => 'bitcoin'])
        ->assertSessionHasErrors(['paid_on' => 'Enter the date it was paid.', 'method']);
});

it("returns not found for another team's payment", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('payments.update', PaymentEloquentModel::factory()->create()->id), ['paid_on' => '2026-11-02', 'method' => 'cash'])
        ->assertNotFound();
});
