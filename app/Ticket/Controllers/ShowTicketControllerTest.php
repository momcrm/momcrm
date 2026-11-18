<?php

declare(strict_types=1);

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Comment\CommentEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\CustomField\CustomFieldEloquentModel;
use App\CustomField\CustomFieldRelationEnum;
use App\CustomObject\CustomObjectEloquentModel;
use App\Location\LocationEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Record\RecordEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia;
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

it("shows a ticket's custom field values with the names of the records they link to", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $reseller = CustomerEloquentModel::factory()->create([...$team, 'name' => 'Reseller Co']);
    $field = CustomFieldEloquentModel::factory()->linkingTo(CustomFieldRelationEnum::Customer)->create($team);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id, 'custom_fields' => [$field->id => $reseller->id]]);

    actingAs($user)
        ->get(route('tickets.show', $ticket->id))
        ->assertInertia(fn (Assert $page) => $page
            ->where("drawer.custom_fields.{$field->id}", $reseller->id)
            ->where('drawer.custom_field_labels', [$reseller->id => 'Reseller Co']),
        );
});

it('lists what links to the ticket', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $team = ['team_id' => $owner->current_team_id];
    $vehicles = CustomObjectEloquentModel::factory()->create([...$team, 'name_plural' => 'Vehicles']);
    $field = CustomFieldEloquentModel::factory()->forObject($vehicles)->linkingTo(CustomFieldRelationEnum::Ticket)->create(['name' => 'Linked ticket']);
    $ticket = TicketEloquentModel::factory()->create([...$team, 'customer_id' => CustomerEloquentModel::factory()->create($team)->id]);
    $van = RecordEloquentModel::factory()->of($vehicles)->create(['name' => 'Van', 'custom_fields' => [$field->id => $ticket->id]]);

    Pest\Laravel\actingAs($owner)
        ->get(route('tickets.show', $ticket->id))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('drawer.linked_records', 1)
            ->where('drawer.linked_records.0.source', 'Vehicles')
            ->where('drawer.linked_records.0.field', 'Linked ticket')
            ->where('drawer.linked_records.0.records.0', ['id' => $van->id, 'title' => 'Van', 'url' => route('records.show', [$vehicles->id, $van->id])]),
        );
});

it('shows who wrote in on a ticket opened by an email', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => null,
        'requester_name' => 'Sam Lee',
        'requester_email' => 'sam@acme.test',
    ]);
    $typed = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawer.requester', ['name' => 'Sam Lee', 'email' => 'sam@acme.test'])
            ->where('drawer.customer_name', null));

    actingAs($user)
        ->get(route('tickets.show', $typed->id))
        ->assertInertia(fn (Assert $page) => $page->where('drawer.requester', null));
});

it('shows a reply that arrived by email as part of the conversation, under its sender\'s name', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['name' => 'Jane Smith']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    CommentEloquentModel::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $user->id, 'body' => 'Checking the toner.', 'created_at' => now()->subHour()]);
    CommentEloquentModel::factory()->fromCustomer('sam@acme.test', 'Sam Lee')->create(['ticket_id' => $ticket->id, 'body' => 'Still offline.']);
    RecordActivity::for(
        type: ActivityTypeEnum::TicketCommented,
        userId: null,
        teamId: $user->current_team_id,
        subject: $ticket,
        details: ['number' => $ticket->number, 'title' => $ticket->title, 'actor_name' => 'Sam Lee'],
    );

    actingAs($user)
        ->get(route('tickets.show', $ticket->id))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('drawer.comments.0.author_name', 'Jane Smith')
            ->where('drawer.comments.0.sender_email', null)
            ->where('drawer.comments.0.visibility', 'internal')
            ->where('drawer.comments.1.author_name', 'Sam Lee')
            ->where('drawer.comments.1.author_id', null)
            ->where('drawer.comments.1.sender_email', 'sam@acme.test')
            ->where('drawer.comments.1.visibility', 'public')
            ->where('drawer.comments.1.can_delete', false)
            ->where('drawer.activity.0.actor', 'Sam Lee'));
});
