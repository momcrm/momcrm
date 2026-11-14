<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('opens a ticket numbered within the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Acme IT']);
    $contact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);
    TicketEloquentModel::factory()->create(['number' => 9]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id, 'number' => 1]);

    $response = actingAs($user)->post(route('tickets.store'), [
        'customer_id' => $customer->id,
        'contact_id' => $contact->id,
        'assignee_id' => $user->id,
        'title' => 'Printer offline',
        'description' => 'Reception printer shows an error.',
        'priority' => 'high',
    ]);

    $ticket = TicketEloquentModel::query()->where('title', 'Printer offline')->sole();
    $response->assertRedirectToRoute('tickets.show', $ticket->id)
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #2 opened for Acme IT.']);
    expect($ticket)
        ->number->toBe(2)
        ->status->toBe('new')
        ->priority->toBe('high')
        ->assignee_id->toBe($user->id);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketOpened->value)->sole())
        ->subject_id->toBe($ticket->id)
        ->team_id->toBe($user->current_team_id);
});

it('rejects a contact who works for a different customer', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $otherContact = ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'contact_id' => $otherContact->id, 'title' => 'X', 'priority' => 'normal'])
        ->assertSessionHasErrors(['contact_id' => 'Choose a contact who works for this customer.']);
});

it('rejects an assignee who is not in the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'assignee_id' => UserEloquentModel::factory()->create()->id, 'title' => 'X', 'priority' => 'normal'])
        ->assertSessionHasErrors(['assignee_id' => 'Choose someone in your team.']);
});

it("rejects another team's customer", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.store'), ['customer_id' => CustomerEloquentModel::factory()->create()->id, 'title' => 'X', 'priority' => 'normal'])
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer this ticket is for.']);

    expect(TicketEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('requires a title', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'priority' => 'normal'])
        ->assertSessionHasErrors(['title' => 'The title field is required.']);
});

it('opens a project ticket without a customer, with dates', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'name' => 'Docs']);

    actingAs($user)
        ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'Write runbook', 'priority' => 'normal', 'starts_on' => '2026-12-01', 'due_on' => '2026-12-05'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Ticket #1 opened for Docs.']);

    expect(TicketEloquentModel::query()->sole())
        ->project_id->toBe($project->id)
        ->customer_id->toBeNull()
        ->starts_on->toBe('2026-12-01')
        ->due_on->toBe('2026-12-05');
});

it("gives a project ticket the project's customer when none is chosen", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $customer->id]);

    actingAs($user)->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X', 'priority' => 'normal']);

    expect(TicketEloquentModel::query()->sole()->customer_id)->toBe($customer->id);
});

it('needs a customer or a project', function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('tickets.store'), ['title' => 'X', 'priority' => 'normal'])
        ->assertSessionHasErrors(['customer_id' => 'Choose the customer this ticket is for, or put it in a project.']);
});

it("rejects another team's project and an archived one", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    foreach ([ProjectEloquentModel::factory()->create(), ProjectEloquentModel::factory()->archived()->create(['team_id' => $user->current_team_id])] as $project) {
        actingAs($user)
            ->post(route('tickets.store'), ['project_id' => $project->id, 'title' => 'X', 'priority' => 'normal'])
            ->assertSessionHasErrors(['project_id' => "Choose one of your team's projects."]);
    }
});

it('rejects a due date before the start date', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $customer = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('tickets.store'), ['customer_id' => $customer->id, 'title' => 'X', 'priority' => 'normal', 'starts_on' => '2026-12-05', 'due_on' => '2026-12-01'])
        ->assertSessionHasErrors(['due_on' => "The due date can't be before the start date."]);
});

it('quick-adds a ticket into a board column and stays on the board', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'status' => 'in_progress', 'position' => 4]);

    actingAs($user)
        ->from(route('tickets.index', ['view' => 'board']))
        ->post(route('tickets.store'), ['title' => 'Quick one', 'priority' => 'normal', 'project_id' => $project->id, 'status' => 'in_progress', 'quick' => '1'])
        ->assertRedirectToRoute('tickets.index', ['view' => 'board']);

    expect(TicketEloquentModel::query()->where('title', 'Quick one')->sole())
        ->status->toBe('in_progress')
        ->position->toBe(5);
});
