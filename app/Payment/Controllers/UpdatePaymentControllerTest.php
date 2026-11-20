<?php

declare(strict_types=1);

use App\Payment\PaymentEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it("changes a payment's date, method, reference and notes but not its amount", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $payment = PaymentEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 3, 'amount_in_cents' => 5000]);

    actingAs($user)
        ->from(route('payments.index'))
        ->put(route('payments.update', $payment->id), [
            'paid_on' => '2026-11-02',
            'method' => 'cheque',
            'reference' => 'CHQ 0042',
            'notes' => 'Posted late.',
            'amount_in_cents' => 1,
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
