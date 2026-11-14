<?php

declare(strict_types=1);

use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('lists every notification with a link to its ticket', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketDueSoonNotification($ticket));

    actingAs($user)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications')
            ->where('notifications.0.message', 'due tomorrow')
            ->where('notifications.0.url', route('tickets.index', ['ticket' => $ticket->id])),
        );
});
