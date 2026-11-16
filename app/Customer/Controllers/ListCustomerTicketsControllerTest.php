<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the customer's open tickets, and the closed ones when asked", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ours = ['team_id' => $user->current_team_id, 'customer_id' => $customer->id];
    TicketEloquentModel::factory()->create([...$ours, 'title' => 'Printer jammed']);
    TicketEloquentModel::factory()->closed()->create([...$ours, 'title' => 'Old request']);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Someone else']);

    actingAs($user)
        ->get(route('customers.tickets', $customer->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customers/workspace/tickets')
            ->where('filters.show', 'open')
            ->where('tickets.total', 1)
            ->where('tickets.data.0.title', 'Printer jammed'),
        );

    actingAs($user)
        ->get(route('customers.tickets', ['customer' => $customer->id, 'show' => 'closed']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('tickets.total', 1)
            ->where('tickets.data.0.title', 'Old request'),
        );
});

it("returns not found for another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->get(route('customers.tickets', CustomerEloquentModel::factory()->create()->id))
        ->assertNotFound();
});
