<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows the edit form for a draft invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('invoices.edit', $invoice->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('invoices/edit')->where('invoice.id', $invoice->id));
});

it('sends you back to an invoice that is no longer a draft', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Sent->value]);

    actingAs($user)
        ->get(route('invoices.edit', $invoice->id))
        ->assertRedirectToRoute('invoices.show', $invoice->id);
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('invoices.edit', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
