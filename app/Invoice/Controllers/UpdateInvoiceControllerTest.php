<?php

declare(strict_types=1);

use App\Charge\ChargeEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Service\ServiceEloquentModel;
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

it("keeps the days a line bills its service for, so the service isn't billed for them again", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $service = ServiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $invoice->customer_id]);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), [...draftPayload(), 'items' => [
            ['description' => 'Backup', 'quantity' => '1', 'unit_price' => '30', 'service_id' => $service->id, 'rental_starts_on' => '2026-12-01', 'rental_ends_on' => '2026-12-31'],
            ['description' => 'Labour', 'quantity' => '1', 'unit_price' => '90', 'rental_starts_on' => '2026-12-01', 'rental_ends_on' => '2026-12-31'],
        ]])
        ->assertSessionHasNoErrors();

    $lines = InvoiceItemEloquentModel::query()->orderBy('position')->get();
    expect($lines->first()->rental_ends_on->toDateString())->toBe('2026-12-31')
        ->and($lines->last()->rental_ends_on)->toBeNull();
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

it("changes a draft's custom field value, keeping the ones not sent", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $kept = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Invoice)->create($team);
    $set = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Invoice)->create($team);
    $invoice = InvoiceEloquentModel::factory()->create([...$team, 'custom_fields' => [$kept->id => 'kept']]);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), [...draftPayload(), 'custom_fields' => [$set->id => 'new']])
        ->assertSessionHasNoErrors();

    expect($invoice->fresh()->custom_fields)->toEqual([$kept->id => 'kept', $set->id => 'new']);
});

it('keeps the one-off charge a line bills, and refuses one billed on another invoice', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $customer = CustomerEloquentModel::query()->findOrFail($invoice->customer_id);
    $charge = ChargeEloquentModel::factory()->forCustomer($customer)->create();
    $elsewhere = ChargeEloquentModel::factory()->forCustomer($customer)->create();
    $other = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 31]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $other->id, 'charge_id' => $elsewhere->id]);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), [...draftPayload(), 'items' => [['description' => 'Laptop', 'quantity' => '1', 'unit_price' => '350', 'charge_id' => $charge->id]]])
        ->assertSessionHasNoErrors();

    expect(InvoiceItemEloquentModel::query()->where('invoice_id', $invoice->id)->sole()->charge_id)->toBe($charge->id);

    actingAs($user)
        ->put(route('invoices.update', $invoice->id), [...draftPayload(), 'items' => [['description' => 'Laptop', 'quantity' => '1', 'unit_price' => '350', 'charge_id' => $elsewhere->id]]])
        ->assertSessionHasErrors(['items.0.charge_id' => 'That charge is already on INV-0031.']);
});
