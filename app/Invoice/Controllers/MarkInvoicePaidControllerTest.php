<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('marks a sent invoice as paid', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Sent->value]);

    actingAs($user)
        ->from(route('invoices.index'))
        ->post(route('invoices.paid', $invoice->id))
        ->assertRedirectToRoute('invoices.index');

    expect($invoice->fresh())
        ->status->toBe('paid')
        ->paid_at->not->toBeNull();
});

it('refuses to mark a draft as paid', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.paid', $invoice->id))
        ->assertSessionHasErrors(['status' => 'A draft invoice cannot be marked paid.']);
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('invoices.paid', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
