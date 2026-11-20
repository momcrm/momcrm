<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it("shows the team's numbers, the user's tickets and the checklist", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'assignee_id' => $user->id, 'title' => 'Mine']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'status_id' => ticketStatusId($user->current_team_id, 'Closed')]);
    $overdue = InvoiceEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => $customer->id,
        'status' => InvoiceStatusEnum::Sent->value,
        'due_on' => now()->subWeek()->toDateString(),
        'tax_rate_basis_points' => 0,
    ]);
    InvoiceItemEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $overdue->id, 'quantity' => 1, 'unit_price_in_cents' => 12345]);
    PaymentAllocationEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'invoice_id' => $overdue->id, 'amount_in_cents' => 2345]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('preferredName', 'Jane Smith')
            ->where('stats', [
                'active_projects' => 0,
                'at_risk_projects' => 0,
                'open_tickets' => 2,
                'closed_this_week' => 0,
                'overdue_tickets' => 0,
                'customers' => 1,
                'unpaid_invoices' => 1,
                'unpaid_totals' => [['currency' => 'AUD', 'total_in_cents' => 10000]],
                'overdue_invoices' => 1,
            ])
            ->has('myTickets', 1)
            ->where('myTickets.0.title', 'Mine')
            ->where('hasSecurity', false)
            ->where('gettingStarted.completed', 2)
            ->where('gettingStarted.total', 5),
        );
});

it("shows the week's deadlines, active projects and recent work", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Due tomorrow', 'due_on' => now()->addDay()->toDateString()]);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Overdue', 'due_on' => now()->subDay()->toDateString()]);
    TicketEloquentModel::factory()->create([...$team, 'title' => 'Closed today', 'status_id' => ticketStatusId($user->current_team_id, 'Closed'), 'closed_at' => now()]);
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Live', 'status' => 'in_progress']);
    ProjectEloquentModel::factory()->create([...$team, 'name' => 'Done', 'status' => 'completed']);
    RecordActivity::for(ActivityTypeEnum::TicketOpened, $user->id, $user->current_team_id, details: ['number' => 1]);
    RecordActivity::for(ActivityTypeEnum::SignedIn, $user->id, $user->current_team_id);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.active_projects', 1)
            ->where('stats.overdue_tickets', 1)
            ->where('stats.closed_this_week', 1)
            ->where('deadlines', fn ($deadlines): bool => collect($deadlines)->pluck('title')->all() === ['Due tomorrow'])
            ->where('projects', fn ($projects): bool => collect($projects)->pluck('name')->all() === ['Live'])
            ->has('activity', 1),
        );
});

it('requires authentication', function (): void {
    get(route('dashboard'))->assertRedirectToRoute('login');
});
