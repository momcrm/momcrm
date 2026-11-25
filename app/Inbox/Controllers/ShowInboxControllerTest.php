<?php

declare(strict_types=1);

use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it("lists the person's notifications in their current team, with unread counts", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'title' => 'Printer']);
    $user->notify(new TicketAssignedNotification($ticket, null));
    $user->notify(new TicketDueSoonNotification($ticket));
    $user->notify(new TicketAssignedNotification(TicketEloquentModel::factory()->create(), null));
    $user->notifications()->where('type', TicketDueSoonNotification::class)->sole()->markAsRead();

    actingAs($user)
        ->get(route('inbox'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('inbox')
            ->has('notifications', 2)
            ->where('counts.all', 1)
            ->where('counts.assignment', 1)
            ->where('counts.update', 0)
            ->where('notifications', fn ($notifications): bool => collect($notifications)->pluck('title')->unique()->all() === ['Printer']),
        );
});

it('filters by kind and unread, and shows the selected ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketAssignedNotification($ticket, null));
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('inbox', ['kind' => 'assignment', 'unread' => 1, 'ticket' => $ticket->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('notifications', 1)
            ->where('notifications.0.kind', 'assignment')
            ->where('filters.unread', true)
            ->where('selected.id', $ticket->id)
            ->has('selected.comments'),
        );
});

it('reloads the notifications and their counts when the inbox changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->get(route('inbox'))->assertOk();

    expect(liveListeners($response, 'notifications'))->toBe(["private-inbox.{$user->id} inbox.changed"])
        ->and(liveListeners($response, 'counts'))->toBe(["private-inbox.{$user->id} inbox.changed"]);
});
