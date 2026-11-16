<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceListEnum;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

function bulkTicket(UserEloquentModel $user, array $attributes = []): TicketEloquentModel
{
    return TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, ...$attributes]);
}

it('sets the status of every selected ticket, closing them', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $tickets = [bulkTicket($user), bulkTicket($user)];
    $untouched = bulkTicket($user);
    $closed = ticketStatusId($user->current_team_id, 'Closed');

    actingAs($user)
        ->from(route('tickets.index'))
        ->patch(route('tickets.bulk.update'), ['ticket_ids' => [$tickets[0]->id, $tickets[1]->id], 'status_id' => $closed])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('tickets.index')
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => '2 tickets updated.']);

    foreach ($tickets as $ticket) {
        expect($ticket->fresh())->status_id->toBe($closed)->closed_at->not->toBeNull();
    }

    expect($untouched->fresh()->status_id)->not->toBe($closed);
});

it('sets the priority and assignee, or unassigns', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teammate = UserEloquentModel::factory()->inTeam($user->current_team_id)->create();
    $ticket = bulkTicket($user, ['assignee_id' => $user->id]);
    $urgent = FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, 'Urgent')->getId()->toString();

    actingAs($user)->patch(route('tickets.bulk.update'), ['ticket_ids' => [$ticket->id], 'priority_id' => $urgent, 'assignee_id' => $teammate->id])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh())->priority_id->toBe($urgent)->assignee_id->toBe($teammate->id);

    actingAs($user)->patch(route('tickets.bulk.update'), ['ticket_ids' => [$ticket->id], 'assignee_id' => null])
        ->assertSessionHasNoErrors();

    expect($ticket->fresh())->assignee_id->toBeNull()->priority_id->toBe($urgent);
});

it('asks what to change when no field is sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)->patch(route('tickets.bulk.update'), ['ticket_ids' => [bulkTicket($user)->id]])
        ->assertSessionHasErrors(['ticket_ids' => 'Choose what to change on the selected tickets.']);
});

it('only assigns people in the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = bulkTicket($user);

    actingAs($user)->patch(route('tickets.bulk.update'), ['ticket_ids' => [$ticket->id], 'assignee_id' => UserEloquentModel::factory()->create()->id])
        ->assertSessionHasErrors(['assignee_id' => 'Choose someone in your team.']);

    expect($ticket->fresh()->assignee_id)->toBeNull();
});

it("changes nothing when any ticket is another team's", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mine = bulkTicket($user);
    $theirs = TicketEloquentModel::factory()->create();
    $closed = ticketStatusId($user->current_team_id, 'Closed');

    actingAs($user)->patch(route('tickets.bulk.update'), ['ticket_ids' => [$mine->id, $theirs->id], 'status_id' => $closed])
        ->assertNotFound();

    expect($mine->fresh()->status_id)->not->toBe($closed);
});

it('changes nothing when closing any one ticket is not allowed', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    AccessRuleEloquentModel::factory()->deny()->create(['team_id' => $owner->current_team_id, 'resources' => ['ticket'], 'actions' => ['close']]);
    $closed = ticketStatusId($owner->current_team_id, 'Closed');
    $alreadyClosed = bulkTicket($owner, ['status_id' => $closed]);
    $open = bulkTicket($owner);

    actingAs($member)->patch(route('tickets.bulk.update'), ['ticket_ids' => [$alreadyClosed->id, $open->id], 'status_id' => $closed])
        ->assertForbidden();

    expect($open->fresh()->status_id)->not->toBe($closed);
});

it('sends guests to log in', function (): void {
    patch(route('tickets.bulk.update'), ['ticket_ids' => [], 'status_id' => null])->assertRedirectToRoute('login');
});
