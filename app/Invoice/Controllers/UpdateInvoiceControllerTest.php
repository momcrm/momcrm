<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function draftPayload(): array
{
    return [
        'issued_on' => '2026-11-14',
        'due_on' => '2026-12-14',
        'tax_rate' => '0',
        'items' => [['description' => 'Replacement line', 'quantity' => '1', 'unit_price' => '10']],
    ];
}

it('replaces the line items of a draft', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    InvoiceItemEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id]);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), draftPayload())
        ->assertRedirectToRoute('invoices.show', $invoice->id);

    expect($invoice->fresh())
        ->tax_rate_basis_points->toBe(0)
        ->and($invoice->fresh()->due_on->toDateString())->toBe('2026-12-14');
    expect(InvoiceItemEloquentModel::query()->pluck('description')->all())->toBe(['Replacement line']);
});

it('forbids editing an invoice that has been sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Sent->value]);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), draftPayload())
        ->assertForbidden();
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('invoices.update', InvoiceEloquentModel::factory()->create()->id), draftPayload())
        ->assertNotFound();
});
