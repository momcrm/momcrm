<?php

declare(strict_types=1);

use App\Choice\ChoiceEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldTargetEnum;
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

it("shares the current team's choices in order, flagging archived ones", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    ChoiceEloquentModel::factory()->ticketPriority()->create(['team_id' => $user->current_team_id, 'name' => 'Retired', 'archived_at' => now()]);
    ChoiceEloquentModel::factory()->ticketPriority()->create(['name' => 'Another team']);

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('choices.ticket_priority', fn ($priorities): bool => collect($priorities)->reject(fn (array $priority): bool => $priority['is_archived'])->pluck('name')->values()->all() === ['Urgent', 'High', 'Normal', 'Low']
                && collect($priorities)->filter(fn (array $priority): bool => $priority['is_archived'])->pluck('name')->values()->all() === ['Retired'])
            ->where('choices.ticket_priority.0.icon', 'chevrons-up')
            ->where('choices.ticket_priority.2.is_default', true)
            ->has('choices.ticket_status', 5),
        );
});

it("shares the current team's live custom fields by record type, in order", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $teamId = $user->current_team_id;
    CustomFieldEloquentModel::factory()->create(['team_id' => $teamId, 'name' => 'Second', 'position' => 1]);
    CustomFieldEloquentModel::factory()->select(['Gold'])->create(['team_id' => $teamId, 'name' => 'First', 'position' => 0]);
    CustomFieldEloquentModel::factory()->archived()->create(['team_id' => $teamId]);
    CustomFieldEloquentModel::factory()->on(CustomFieldTargetEnum::Customer)->create(['team_id' => $teamId, 'name' => 'Region']);
    CustomFieldEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('customFields.ticket', 2)
            ->where('customFields.ticket.0.name', 'First')
            ->where('customFields.ticket.0.type', 'select')
            ->where('customFields.ticket.0.options.0.name', 'Gold')
            ->where('customFields.ticket.1.name', 'Second')
            ->has('customFields.customer', 1)
            ->where('customFields.customer.0.name', 'Region')
            ->where('customFields.contact', [])
            ->where('customFields.project', []),
        );
});
