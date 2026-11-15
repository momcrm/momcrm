<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Choice\Actions\FindChoice;
use App\Choice\ChoiceEloquentModel;
use App\Choice\ChoiceListEnum;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

function teamPriorityId(UserEloquentModel $user, string $name): string
{
    return FindChoice::byNameForTeam($user->current_team_id, ChoiceListEnum::TicketPriority, $name)->getId()->toString();
}

function patchableTicket(UserEloquentModel $user, array $attributes = []): TicketEloquentModel
{
    return TicketEloquentModel::factory()->create([
        'team_id' => $user->current_team_id,
        'customer_id' => CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id])->id,
        ...$attributes,
    ]);
}

it('changes only the fields sent', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user, ['title' => 'Printer']);
    $urgent = teamPriorityId($user, 'Urgent');
    $customerId = $ticket->customer_id;

    actingAs($user)
        ->from(route('tickets.index'))
        ->patch(route('tickets.patch', $ticket->id), ['priority_id' => $urgent, 'due_on' => '2026-12-01', 'estimate_minutes' => 90])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('tickets.index');

    expect($ticket->fresh())
        ->title->toBe('Printer')
        ->priority_id->toBe($urgent)
        ->due_on->toBe('2026-12-01')
        ->estimate_minutes->toBe(90)
        ->customer_id->toBe($customerId);
});

it('closes a ticket from the drawer, stamping the closed time', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['status_id' => ticketStatusId($user->current_team_id, 'Closed')]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('sets the labels', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    [$keep, $add] = ChoiceEloquentModel::factory()->ticketLabel()->count(2)->create(['team_id' => $user->current_team_id]);
    $drop = ChoiceEloquentModel::factory()->ticketLabel()->create(['team_id' => $user->current_team_id]);
    $ticket->labels()->attach([$keep->id, $drop->id]);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['label_ids' => [$keep->id, $add->id]]);

    expect($ticket->fresh()->labels->pluck('id')->sort()->values()->all())->toBe(collect([$keep->id, $add->id])->sort()->values()->all());
});

it("rejects another team's label", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['label_ids' => [ChoiceEloquentModel::factory()->ticketLabel()->create()->id]])
        ->assertSessionHasErrors('label_ids.0');
});

it('clears the contact when the customer changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $ticket->update(['contact_id' => ContactEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'customer_id' => $ticket->customer_id])->id]);
    $other = CustomerEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['customer_id' => $other->id])->assertSessionHasNoErrors();

    expect($ticket->fresh())->customer_id->toBe($other->id)->contact_id->toBeNull();
});

it('will not leave a ticket with neither customer nor project', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $project = ProjectEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = patchableTicket($user, ['customer_id' => null, 'project_id' => $project->id]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['project_id' => null])
        ->assertSessionHasErrors('customer_id');
});

it("rejects another team's priority", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $otherTeamsUrgent = teamPriorityId(UserEloquentModel::factory()->withPersonalTeam()->create(), 'Urgent');

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['priority_id' => $otherTeamsUrgent])
        ->assertSessionHasErrors(['priority_id' => "Choose one of your team's priorities."]);
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->patch(route('tickets.patch', TicketEloquentModel::factory()->create()->id), ['title' => 'Mine'])
        ->assertNotFound();
});

it('applies the close rules when an edit closes or reopens the ticket', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    AccessRuleEloquentModel::factory()->deny()->create(['team_id' => $owner->current_team_id, 'resources' => ['ticket'], 'actions' => ['close', 'reopen']]);
    $new = ticketStatusId($owner->current_team_id, 'New');
    $closedStatus = ticketStatusId($owner->current_team_id, 'Closed');
    $open = patchableTicket($owner, ['status_id' => $new]);
    $closed = patchableTicket($owner, ['status_id' => $closedStatus]);

    $urgent = teamPriorityId($owner, 'Urgent');

    actingAs($member)->patch(route('tickets.patch', $open->id), ['priority_id' => $urgent])->assertSessionHasNoErrors();
    actingAs($member)->patch(route('tickets.patch', $open->id), ['status_id' => $closedStatus])->assertForbidden();
    actingAs($member)->patch(route('tickets.patch', $closed->id), ['status_id' => $new])->assertForbidden();

    expect($open->fresh())->priority_id->toBe($urgent)->status_id->toBe($new)
        ->and($closed->fresh()->status_id)->toBe($closedStatus);
});

it('keeps an archived status the ticket already has, but does not move tickets into one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $ticket = patchableTicket($user, ['status_id' => ticketStatusId($teamId, 'Waiting on supplier')]);
    $other = patchableTicket($user);
    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($ticket->status_id)->update(['archived_at' => now()]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['status_id' => $ticket->status_id, 'title' => 'Renamed'])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['status_id' => $ticket->status_id])
        ->assertSessionHasErrors(['status_id' => "Choose one of your team's statuses."]);

    expect($ticket->fresh()->title)->toBe('Renamed');
});

it('keeps an archived label the ticket already has, but does not add one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    $other = patchableTicket($user);
    $live = ChoiceEloquentModel::factory()->ticketLabel()->create(['team_id' => $user->current_team_id]);
    $archived = ChoiceEloquentModel::factory()->ticketLabel()->create(['team_id' => $user->current_team_id, 'archived_at' => now()]);
    $ticket->labels()->attach($archived);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['label_ids' => [$archived->id, $live->id]])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['label_ids' => [$archived->id]])
        ->assertSessionHasErrors('label_ids.0');

    expect($ticket->labels()->pluck('choices.id')->sort()->values()->all())->toBe(collect([$archived->id, $live->id])->sort()->values()->all());
});

it('sets the type and department, keeping archived ones the ticket has but not choosing one from another list', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teamId = $user->current_team_id;
    $incident = FindChoice::byNameForTeam($teamId, ChoiceListEnum::TicketType, 'Incident')->getId()->toString();
    $support = FindChoice::byNameForTeam($teamId, ChoiceListEnum::TicketDepartment, 'Support')->getId()->toString();
    $ticket = patchableTicket($user);
    $other = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['type_id' => $incident, 'department_id' => $support])
        ->assertSessionHasNoErrors();

    ChoiceEloquentModel::query()->withoutGlobalScopes()->whereKey($incident)->update(['archived_at' => now()]);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['title' => 'Renamed'])
        ->assertSessionHasNoErrors();

    actingAs($user)
        ->patch(route('tickets.patch', $other->id), ['type_id' => $incident, 'department_id' => teamPriorityId($user, 'Urgent')])
        ->assertSessionHasErrors([
            'type_id' => "Choose one of your team's ticket types.",
            'department_id' => "Choose one of your team's departments.",
        ]);

    expect($ticket->fresh())->type_id->toBe($incident)->department_id->toBe($support)->title->toBe('Renamed')
        ->and($other->fresh())->type_id->toBeNull()->department_id->toBeNull();
});
