<?php

declare(strict_types=1);

use App\AccessRule\AccessRuleEloquentModel;
use App\Contact\ContactEloquentModel;
use App\Customer\CustomerEloquentModel;
use App\Label\LabelEloquentModel;
use App\Project\ProjectEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

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
    $ticket = patchableTicket($user, ['title' => 'Printer', 'priority' => 'normal']);
    $customerId = $ticket->customer_id;

    actingAs($user)
        ->from(route('tickets.index'))
        ->patch(route('tickets.patch', $ticket->id), ['priority' => 'urgent', 'due_on' => '2026-12-01', 'estimate_minutes' => 90])
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('tickets.index');

    expect($ticket->fresh())
        ->title->toBe('Printer')
        ->priority->toBe('urgent')
        ->due_on->toBe('2026-12-01')
        ->estimate_minutes->toBe(90)
        ->customer_id->toBe($customerId);
});

it('closes a ticket from the drawer, stamping the closed time', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['status' => 'closed']);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it('sets the labels', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);
    [$keep, $add] = LabelEloquentModel::factory()->count(2)->create(['team_id' => $user->current_team_id]);
    $drop = LabelEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket->labels()->attach([$keep->id, $drop->id]);

    actingAs($user)->patch(route('tickets.patch', $ticket->id), ['label_ids' => [$keep->id, $add->id]]);

    expect($ticket->fresh()->labels->pluck('id')->sort()->values()->all())->toBe(collect([$keep->id, $add->id])->sort()->values()->all());
});

it("rejects another team's label", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = patchableTicket($user);

    actingAs($user)
        ->patch(route('tickets.patch', $ticket->id), ['label_ids' => [LabelEloquentModel::factory()->create()->id]])
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

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->patch(route('tickets.patch', TicketEloquentModel::factory()->create()->id), ['priority' => 'low'])
        ->assertNotFound();
});

it('applies the close rules when an edit closes or reopens the ticket', function (): void {
    $owner = UserEloquentModel::factory()->withPersonalTeam()->create();
    $member = UserEloquentModel::factory()->inTeam($owner->current_team_id)->create();
    AccessRuleEloquentModel::factory()->deny()->create(['team_id' => $owner->current_team_id, 'resources' => ['ticket'], 'actions' => ['close', 'reopen']]);
    $open = patchableTicket($owner, ['status' => 'new']);
    $closed = patchableTicket($owner, ['status' => 'closed']);

    actingAs($member)->patch(route('tickets.patch', $open->id), ['priority' => 'urgent'])->assertSessionHasNoErrors();
    actingAs($member)->patch(route('tickets.patch', $open->id), ['status' => 'closed'])->assertForbidden();
    actingAs($member)->patch(route('tickets.patch', $closed->id), ['status' => 'new'])->assertForbidden();

    expect($open->fresh())->priority->toBe('urgent')->status->toBe('new')
        ->and($closed->fresh()->status)->toBe('closed');
});
