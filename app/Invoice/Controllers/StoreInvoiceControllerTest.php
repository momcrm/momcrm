<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomField\CustomFieldTypeEnum;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Team\TeamEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function invoicePayload(string $customerId, array $overrides = []): array
{
    return [
        'customer_id' => $customerId,
        'issued_on' => '2026-11-14',
        'due_on' => '2026-11-28',
        'tax_rate' => '10',
        'purchase_order' => 'PO-77',
        'items' => [
            ['description' => 'Managed IT support', 'quantity' => '3', 'unit_price' => '49.95'],
            ['description' => 'Call-out', 'quantity' => '1', 'unit_price' => '120'],
        ],
        ...$overrides,
    ];
}

it('saves a draft invoice with its line items in cents', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    $response = actingAs($user)->post(route('invoices.store'), invoicePayload($customer->id));

    $invoice = InvoiceEloquentModel::query()->sole();
    $response->assertRedirectToRoute('invoices.show', $invoice->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'INV-0001 saved as a draft.']);
    expect($invoice)
        ->number->toBe(1)
        ->status->toBe('draft')
        ->tax_rate_basis_points->toBe(1000)
        ->purchase_order->toBe('PO-77')
        ->and($invoice->issued_on->toDateString())->toBe('2026-11-14');
    expect(InvoiceItemEloquentModel::query()->orderBy('position')->get(['description', 'quantity', 'unit_price_in_cents', 'position'])->toArray())
        ->toBe([
            ['description' => 'Managed IT support', 'quantity' => 3, 'unit_price_in_cents' => 4995, 'position' => 0],
            ['description' => 'Call-out', 'quantity' => 1, 'unit_price_in_cents' => 12000, 'position' => 1],
        ]);
});

it("puts the invoice in the customer's currency", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'currency' => 'SGD']);

    actingAs($user)->post(route('invoices.store'), invoicePayload($customer->id))->assertSessionHasNoErrors();

    expect(InvoiceEloquentModel::query()->sole()->currency)->toBe('SGD');
});

it("numbers the invoice with the team's prefix, which it keeps when the prefix changes", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    TeamEloquentModel::query()->findOrFail($user->current_team_id)->update(['invoice_prefix' => 'CEB-']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.store'), invoicePayload($customer->id))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'CEB-0001 saved as a draft.']);

    TeamEloquentModel::query()->findOrFail($user->current_team_id)->update(['invoice_prefix' => '']);
    actingAs($user)->post(route('invoices.store'), invoicePayload($customer->id));

    expect(InvoiceEloquentModel::query()->orderBy('number')->pluck('prefix')->all())->toBe(['CEB-', '']);
});

it('requires at least one line item', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.store'), invoicePayload($customer->id, ['items' => []]))
        ->assertSessionHasErrors(['items' => 'Add at least one line item.']);

    expect(InvoiceEloquentModel::query()->exists())->toBeFalse();
});

it('requires each line to have a description and price', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.store'), invoicePayload($customer->id, ['items' => [['description' => '', 'quantity' => '1', 'unit_price' => '']]]))
        ->assertSessionHasErrors([
            'items.0.description' => 'Describe each line item.',
            'items.0.unit_price_in_cents' => 'Give each line item a price.',
        ]);
});

it('rejects a due date before the issue date', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('invoices.store'), invoicePayload($customer->id, ['due_on' => '2026-11-01']))
        ->assertSessionHasErrors(['due_on' => 'The due on field must be a date after or equal to issued on.']);
});

it("rejects another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('invoices.store'), invoicePayload(CustomerEloquentModel::factory()->create()->id))
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer to invoice.']);
});

it('saves an invoice with its custom field values, rejecting one of the wrong type', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create($team);
    $text = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Invoice)->create($team);
    $date = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Invoice)->ofType(CustomFieldTypeEnum::Date)->create([...$team, 'name' => 'Work done on']);

    actingAs($user)
        ->post(route('invoices.store'), [...invoicePayload($customer->id), 'custom_fields' => [$date->id => 'last week']])
        ->assertSessionHasErrors(["custom_fields.{$date->id}" => 'The Work done on field must match the format Y-m-d.']);

    actingAs($user)
        ->post(route('invoices.store'), [...invoicePayload($customer->id), 'custom_fields' => [$text->id => 'Cost centre 7', $date->id => '2026-11-30']])
        ->assertSessionHasNoErrors();

    expect(InvoiceEloquentModel::query()->sole()->custom_fields)->toEqual([$text->id => 'Cost centre 7', $date->id => '2026-11-30']);
});
