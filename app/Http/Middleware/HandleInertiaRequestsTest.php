<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shares the open ticket (not subtask) and unread notification counts shown in the sidebar', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = fn (array $attributes) => TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, ...$attributes]);

    $ticket(['assignee_id' => $user->id]);
    $ticket(['assignee_id' => null, 'status_id' => ticketStatusId($user->current_team_id, 'In progress')]);
    $ticket(['assignee_id' => $user->id, 'status_id' => ticketStatusId($user->current_team_id, 'Closed')]);
    $ticket(['parent_id' => $ticket([])->id, 'assignee_id' => $user->id]);
    TicketEloquentModel::factory()->create();
    $user->notify(new TicketDueSoonNotification(TicketEloquentModel::query()->first()));

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebarCounts.openTickets', 3)
            ->where('sidebarCounts.myOpenTickets', 1)
            ->where('sidebarCounts.unreadNotifications', 1),
        );
});

it("shares the current team's choices, in order and without archived ones", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    ChoiceEloquentModel::factory()->ticketPriority()->create(['team_id' => $user->current_team_id, 'name' => 'Retired', 'archived_at' => now()]);
    ChoiceEloquentModel::factory()->ticketPriority()->create(['name' => 'Another team']);

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('choices.ticket_priority', fn ($priorities): bool => collect($priorities)->pluck('name')->all() === ['Urgent', 'High', 'Normal', 'Low'])
            ->where('choices.ticket_priority.0.icon', 'chevrons-up')
            ->where('choices.ticket_priority.2.is_default', true)
            ->has('choices.ticket_status', 5),
        );
});
