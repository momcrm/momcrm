<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Service\ServiceEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("opens the customer's dashboard with what they owe, split into current and overdue", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $bill = function (string $status, string $dueOn) use ($user, $customer): InvoiceEloquentModel {
        $invoice = InvoiceEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => $status, 'due_on' => $dueOn, 'tax_rate_basis_points' => 0]);
        InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $invoice->id, 'quantity' => 1, 'unit_price_in_cents' => 10000]);

        return $invoice;
    };
    $bill('sent', now()->addDays(5)->toDateString());
    $bill('sent', now()->subDay()->toDateString());
    $draft = $bill('draft', now()->addDays(5)->toDateString());
    $bill('paid', now()->subDay()->toDateString());

    actingAs($user)
        ->get(route('customers.show', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/dashboard')
            ->where('workspace.customer.name', 'Acme IT')
            ->where('workspace.counts.invoices', 4)
            ->where('money.balance_in_cents', 20000)
            ->where('money.current_in_cents', 10000)
            ->where('money.overdue_in_cents', 10000)
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
    $service(['name' => 'Already cancelled', 'status' => 'cancelled', 'ends_on' => now()->addDays(10)->toDateString()]);
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
