<?php

declare(strict_types=1);

use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('marks everything in the current team read', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $user->notify(new TicketDueSoonNotification($ticket));
    $user->notify(new TicketDueSoonNotification($ticket));
    $user->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create()));

    actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'All caught up.']);

    expect($user->unreadNotifications()->count())->toBe(1);
});
