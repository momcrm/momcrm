<?php

declare(strict_types=1);

use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
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

it('shows an assignee their ticket but not the members-only project it sits in', function (): void {
    $member = UserEloquentModel::factory()->withPersonalTeam()->create();
    $assignee = UserEloquentModel::factory()->create(['current_team_id' => $member->current_team_id]);
    TeamUserEloquentModel::query()->create(['team_id' => $member->current_team_id, 'user_id' => $assignee->id, 'role' => 'member']);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'visibility' => 'restricted']);
    $project->members()->attach($member->id);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $member->current_team_id, 'project_id' => $project->id, 'assignee_id' => $assignee->id]);

    actingAs($assignee)
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('project', null));

    actingAs($member)
        ->get(route('tickets.show', $ticket->id))
        ->assertInertia(fn (Assert $page) => $page->where('project.id', $project->id));
});
