<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Record\RecordEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows an invoice with its lines and totals', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'tax_rate_basis_points' => 1000]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 2, 'unit_price_in_cents' => 2500]);

    actingAs($user)
        ->get(route('invoices.show', $invoice->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/show')
            ->has('items', 1)
            ->where('items.0.line_total_in_cents', 5000)
            ->where('totals', ['subtotal_in_cents' => 5000, 'tax_in_cents' => 500, 'total_in_cents' => 5500]),
        );
});

it("returns not found for another team's invoice", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('invoices.show', InvoiceEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('sends the invoice its custom field values, the names they link to, and what links to it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme']);
    $payer = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Invoice)->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $invoice = InvoiceEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'custom_fields' => [$payer->id => $customer->id]]);
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $link = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Invoice)->create(['name' => 'Purchase invoice']);
    RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$link->id => $invoice->id]]);

    Pest\Laravel\actingAs($user)
        ->get(route('invoices.show', $invoice->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where("invoice.custom_fields.{$payer->id}", $customer->id)
            ->where('customFieldLabels', [$customer->id => 'Acme'])
            ->where('linkedRecords.0.source', 'Vehicles')
            ->where('linkedRecords.0.field', 'Purchase invoice')
            ->where('linkedRecords.0.records.0.title', 'Van'),
        );
});
