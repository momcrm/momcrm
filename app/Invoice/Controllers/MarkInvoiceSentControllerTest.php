<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('marks a draft as sent and stamps when', function (): void {
    travelTo('2026-11-14 11:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.sent', $invoice->id))
        ->assertRedirectToRoute('invoices.show', $invoice->id);

    expect($invoice->fresh())
        ->status->toBe('sent')
        ->and($invoice->fresh()->sent_at?->toDateTimeString())->toBe('2026-11-14 11:00:00');
});

it('refuses to send an invoice that is not a draft', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Paid->value]);

    actingAs($user)
        ->post(route('invoices.sent', $invoice->id))
        ->assertSessionHasErrors(['status' => 'A paid invoice cannot be marked sent.']);

    expect($invoice->fresh()->status)->toBe('paid');
});
