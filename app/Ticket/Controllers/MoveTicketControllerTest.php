<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Choice\ChoiceEloquentModel;
use App\Ticket\Events\TicketChanged;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

use function Illuminate\Support\defer;
use function Pest\Laravel\actingAs;

it('moves a ticket to another column at the given place', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $inProgress = ticketStatusId($user->current_team_id, 'In progress');
    $first = TicketEloquentModel::factory()->inStatus('In progress')->create([...$team, 'position' => 0]);
    $second = TicketEloquentModel::factory()->inStatus('In progress')->create([...$team, 'position' => 1]);
    $moving = TicketEloquentModel::factory()->create([...$team, 'position' => 0]);

    actingAs($user)
        ->from(route('tickets.index'))
        ->put(route('tickets.move', $moving->id), ['status_id' => $inProgress, 'position' => 1])
        ->assertRedirectToRoute('tickets.index');

    expect($moving->fresh())->status_id->toBe($inProgress)->position->toBe(1)
        ->and($first->fresh()->position)->toBe(0)
        ->and($second->fresh()->position)->toBe(2);
});

it('reorders a ticket within its column', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    $tickets = collect(range(0, 2))->map(fn (int $position) => TicketEloquentModel::factory()->create([...$team, 'position' => $position]));

    actingAs($user)->put(route('tickets.move', $tickets[2]->id), ['status_id' => ticketStatusId($user->current_team_id, 'New'), 'position' => 0]);

    expect($tickets->map(fn (TicketEloquentModel $ticket): int => $ticket->fresh()->position)->all())->toBe([1, 2, 0]);
});

it('stamps the closed time when dropped into a closed status', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->put(route('tickets.move', $ticket->id), ['status_id' => ticketStatusId($user->current_team_id, 'Closed'), 'position' => 0]);

    expect($ticket->fresh()->closed_at)->not->toBeNull();
});

it("rejects another team's status", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $otherTeam = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->put(route('tickets.move', $ticket->id), ['status_id' => ticketStatusId($otherTeam->current_team_id, 'Closed'), 'position' => 0])
        ->assertSessionHasErrors(['status_id' => "Choose one of your team's statuses."]);

    expect($ticket->fresh()->closed_at)->toBeNull();
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->put(route('tickets.move', TicketEloquentModel::factory()->create()->id), ['status_id' => '01a1ddf1-0000-7000-8000-000000000000', 'position' => 0])
        ->assertNotFound();
});

it("treats a team's own closed status as closed", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create(['onboarded_at' => now()]);
    $wontFix = ChoiceEloquentModel::factory()->closedTicketStatus()->create(['team_id' => $user->current_team_id, 'name' => "Won't fix"]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)->put(route('tickets.move', $ticket->id), ['status_id' => $wontFix->id, 'position' => 0])->assertSessionHasNoErrors();

    expect($ticket->fresh()->closed_at)->not->toBeNull()
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketClosed->value)->exists())->toBeTrue();
    actingAs($user)
        ->get(route('tickets.index'))
        ->assertInertia(fn (Assert $page) => $page->where('tickets.total', 0)->where('sidebarCounts.openTickets', 0));
});

it('tells open pages when a ticket is reordered within its column', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $team = ['team_id' => $user->current_team_id];
    TicketEloquentModel::factory()->create([...$team, 'position' => 0]);
    $moving = TicketEloquentModel::factory()->create([...$team, 'position' => 1]);
    defer()->invoke();
    Event::fake([TicketChanged::class]);

    actingAs($user)->put(route('tickets.move', $moving->id), ['status_id' => ticketStatusId($user->current_team_id, 'New'), 'position' => 0]);

    Event::assertDispatched(TicketChanged::class, fn (TicketChanged $event): bool => $event->ticketId === $moving->id);
});
