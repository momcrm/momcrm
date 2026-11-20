<?php

declare(strict_types=1);

use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('voids a draft or sent invoice', function (InvoiceStatusEnum $status): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => $status->value]);

    actingAs($user)
        ->from(route('invoices.index'))
        ->post(route('invoices.void', $invoice->id))
        ->assertRedirectToRoute('invoices.index');

    expect($invoice->fresh()->status)->toBe('void');
})->with([InvoiceStatusEnum::Draft, InvoiceStatusEnum::Sent]);

it('refuses to void a paid invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => InvoiceStatusEnum::Paid->value]);

    actingAs($user)
        ->post(route('invoices.void', $invoice->id))
        ->assertSessionHasErrors(['status' => 'A paid invoice cannot be marked void.']);
});
