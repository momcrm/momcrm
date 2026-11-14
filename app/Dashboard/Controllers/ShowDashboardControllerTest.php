<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it("shows the team's numbers, the user's tickets and the checklist", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'assignee_id' => $user->id, 'title' => 'Mine']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status' => TicketStatusEnum::Closed->value]);
    $overdue = InvoiceEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatusEnum::Sent->value,
        'due_on' => now()->subWeek()->toDateString(),
        'tax_rate_basis_points' => 0,
    ]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $overdue->id, 'quantity' => 1, 'unit_price_in_cents' => 12345]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('firstName', 'Jane')
            ->where('stats', [
                'customers' => 1,
                'open_tickets' => 2,
                'unpaid_invoices' => 1,
                'unpaid_total_in_cents' => 12345,
                'overdue_invoices' => 1,
            ])
            ->has('myTickets', 1)
            ->where('myTickets.0.title', 'Mine')
            ->where('hasSecurity', false)
            ->where('gettingStarted.completed', 2)
            ->where('gettingStarted.total', 5),
        );
});

it('requires authentication', function (): void {
    get(route('dashboard'))->assertRedirectToRoute('login');
});
