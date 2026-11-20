<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists invoices newest first with their totals', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1]);
    $newest = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 2]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $newest->id, 'quantity' => 2, 'unit_price_in_cents' => 5000]);
    InvoiceEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invoices/index')
            ->where('invoices.total', 2)
            ->where('invoices.data.0.reference', 'INV-0002')
            ->where('invoices.data.0.customer_name', 'Acme IT')
            ->where('invoices.data.0.total_in_cents', 11000),
        );
});

it('filters by status and finds by number', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1]);
    InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 12, 'status' => InvoiceStatusEnum::Paid->value]);

    actingAs($user)
        ->get(route('invoices.index', ['status' => 'paid']))
        ->assertInertia(fn (Assert $page) => $page->where('invoices.total', 1)->where('filters.status', 'paid'));

    actingAs($user)
        ->get(route('invoices.index', ['search' => 'INV-0012']))
        ->assertInertia(fn (Assert $page) => $page->where('invoices.total', 1)->where('invoices.data.0.number', 12));

    actingAs($user)
        ->get(route('invoices.index', ['search' => 'CEB2026-0012']))
        ->assertInertia(fn (Assert $page) => $page->where('invoices.total', 1)->where('invoices.data.0.number', 12));
});

it('leaves the open invoice out of the page links, keeping the filters', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $invoices = InvoiceEloquentModel::factory()->count(26)->sequence(fn ($sequence) => ['number' => $sequence->index + 1])->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)
        ->get(route('invoices.index', ['status' => 'draft', 'invoice' => $invoices->first()->id]))
        ->assertInertia(fn (Assert $page) => $page->where('invoices.next_page_url', route('invoices.index', ['status' => 'draft', 'page' => 2])));
});
