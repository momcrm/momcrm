<?php

declare(strict_types=1);

use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Location\LocationEloquentModel;
use App\Project\ProjectEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("shows a ticket's full details, the same as the drawer, with its contact and location", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $team = ['team_id' => $user->current_team_id];
    $customer = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Acme IT']);
    $contact = ContactEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'first_name' => 'Sam', 'last_name' => 'Lee', 'email' => 'sam@acme.test']);
    $location = LocationEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'name' => 'Head office']);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => $customer->id, 'contact_id' => $contact->id, 'location_id' => $location->id, 'title' => 'Printer offline', 'assignee_id' => $user->id]);

    actingAs($user)
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tickets/show')
            ->where('drawer.title', 'Printer offline')
            ->where('drawer.customer_name', 'Acme IT')
            ->where('drawer.assignee_name', 'Jane Smith')
            ->where('drawer.contact.name', 'Sam Lee')
            ->where('drawer.contact.email', 'sam@acme.test')
            ->where('drawer.location_name', 'Head office')
            ->has('drawer.comments')
            ->has('drawer.subtasks')
            ->has('options.members'),
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
        ->assertInertia(fn (Assert $page) => $page->where('drawer.project_name', null));

    actingAs($member)
        ->get(route('tickets.show', $ticket->id))
        ->assertInertia(fn (Assert $page) => $page->where('drawer.project_name', $project->name));
});
