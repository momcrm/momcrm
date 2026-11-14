<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('shows a ticket with its customer and assignee', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'title' => 'Printer offline', 'assignee_id' => $user->id]);

    actingAs($user)
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/show')
            ->where('ticket.title', 'Printer offline')
            ->where('customer.name', 'Acme IT')
            ->where('assigneeName', 'Jane Smith')
            ->where('contact', null),
        );
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('tickets.show', TicketEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
