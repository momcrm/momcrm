<?php

declare(strict_types=1);

use App\Contract\ContractEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("opens the customer's dashboard with what they owe after payments, split into current and overdue", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $bill = function (string $status, string $dueOn) use ($user, $customer): InvoiceEloquentModel {
        $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => $status, 'due_on' => $dueOn, 'tax_rate_basis_points' => 0]);
        InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

        return $invoice;
    };
    $bill('sent', now()->addDays(5)->toDateString());
    $overdue = $bill('sent', now()->subDay()->toDateString());
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $overdue->id, 'amount_in_cents' => 2500]);
    $draft = $bill('draft', now()->addDays(5)->toDateString());
    $bill('paid', now()->subDay()->toDateString());

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/dashboard')
            ->where('workspace.customer.name', 'Acme IT')
            ->where('workspace.counts.invoices', 4)
            ->where('money.balance_in_cents', 17500)
            ->where('money.current_in_cents', 10000)
            ->where('money.overdue_in_cents', 7500)
            ->has('draftInvoices', 1)
            ->where('draftInvoices.0.id', $draft->id),
        );
});

it('lists open tickets and active services that end within 30 days', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Printer jammed']);
    $service = fn (array $attributes) => ServiceEloquentModel::factory()->create([...$attributes, 'team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $service(['name' => 'Ending soon', 'status' => 'active', 'ends_on' => now()->addDays(10)->toDateString()]);
    $service(['name' => 'Ends later', 'status' => 'active', 'ends_on' => now()->addDays(60)->toDateString()]);
    $service(['name' => 'Already cancelled', 'status' => 'inactive', 'ends_on' => now()->addDays(10)->toDateString()]);
    $service(['name' => 'No end', 'status' => 'active', 'ends_on' => null]);

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where('openTickets.0.title', 'Printer jammed')
            ->where('workspace.counts.tickets', 1)
            ->has('expiringServices', 1)
            ->where('expiringServices.0.name', 'Ending soon'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.show', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});

it('lists active contracts that end within 30 days', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $contract = fn (array $attributes) => ContractEloquentModel::factory()->create([...$attributes, 'team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    $contract(['title' => 'Ending soon', 'status' => 'active', 'ends_on' => now()->addDays(20)->toDateString()]);
    $contract(['title' => 'Ends later', 'status' => 'active', 'ends_on' => now()->addDays(90)->toDateString()]);
    $contract(['title' => 'Cancelled', 'status' => 'inactive', 'ends_on' => now()->addDays(20)->toDateString()]);

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('expiringContracts', 1)
            ->where('expiringContracts.0.title', 'Ending soon'),
        );
});

it('lists what links to the customer through record-link fields', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $user->current_team_id];
    $acme = CustomerEloquentModel::factory()->create($team);
    $parent = CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->linkingTo(CustomFieldRelationEnum::Customer)->create([...$team, 'name' => 'Parent company']);
    $child = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme Retail', 'custom_fields' => [$parent->id => $acme->id]]);

    actingAs($user)
        ->get(route('customers.show', $acme->id))
        ->assertInertia(fn (Assert $page) => $page
            ->has('linkedRecords', 1)
            ->where('linkedRecords.0.source', 'Customers')
            ->where('linkedRecords.0.field', 'Parent company')
            ->where('linkedRecords.0.records.0.title', 'Acme Retail'),
        );
});
