<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('drafts an invoice for each customer picked, with just the lines picked', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $acme = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $zed = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $picked = ChargeEloquentModel::factory()->forCustomer($acme)->create(['charged_on' => '2026-11-20']);
    ChargeEloquentModel::factory()->forCustomer($acme)->create(['charged_on' => '2026-11-20']);
    ChargeEloquentModel::factory()->forCustomer($zed)->create(['charged_on' => '2026-11-20']);

    $response = actingAs($user)
        ->post(route('invoices.run.store'), ['date' => '2026-12-02', 'customers' => [$acme->id => ["charge:{$picked->id}"]]])
        ->assertRedirectToRoute('invoices.run', ['date' => '2026-12-02']);

    $invoice = InvoiceEloquentModel::query()->sole();
    $response->assertInertiaFlash('draftedInvoiceIds', [$invoice->id])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '1 invoice drafted.']);

    expect($invoice->customer_id)->toBe($acme->id)
        ->and(InvoiceItemEloquentModel::query()->pluck('charge_id')->all())->toBe([$picked->id]);
});

it('drafts nothing when a line was billed since, or a customer is not in the run', function (Closure $customers): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $acme = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $charge = ChargeEloquentModel::factory()->forCustomer($acme)->create(['charged_on' => '2026-11-20']);
    $excluded = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'billing_type' => 'excluded']);
    $theirs = ChargeEloquentModel::factory()->forCustomer($excluded)->create(['charged_on' => '2026-11-20']);

    actingAs($user)
        ->post(route('invoices.run.store'), ['date' => '2026-12-02', 'customers' => $customers($acme, $charge, $excluded, $theirs)])
        ->assertSessionHasErrors(['customers' => 'Some lines were billed or changed since. Look again before drafting.']);

    expect(InvoiceEloquentModel::query()->count())->toBe(0);
})->with([
    'a line that never was' => [fn ($acme, $charge): array => [$acme->id => ["charge:{$charge->id}", 'charge:gone']]],
    'an excluded customer' => [fn ($acme, $charge, $excluded, $theirs): array => [$excluded->id => ["charge:{$theirs->id}"]]],
]);
