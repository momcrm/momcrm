<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\Ticket\TicketStatusEnum;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shares the open ticket counts shown in the sidebar', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = fn (array $attributes) => TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, ...$attributes]);

    $ticket(['assignee_id' => $user->id]);
    $ticket(['assignee_id' => null, 'status' => TicketStatusEnum::InProgress->value]);
    $ticket(['assignee_id' => $user->id, 'status' => TicketStatusEnum::Closed->value]);
    TicketEloquentModel::factory()->create();

    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('sidebarCounts.openTickets', 2)
            ->where('sidebarCounts.myOpenTickets', 1),
        );
});
