<?php

declare(strict_types=1);

use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('marks a notification read and unread again', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $user->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])));
    $notification = $user->notifications()->sole();

    actingAs($user)->post(route('notifications.read', $notification->id));
    expect($notification->fresh()->read_at)->not->toBeNull();

    actingAs($user)->post(route('notifications.read', $notification->id), ['read' => false]);
    expect($notification->fresh()->read_at)->toBeNull();
});

it("returns not found for someone else's notification", function (): void {
    $other = UserEloquentModel::factory()->withPersonalTeam()->create();
    $other->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create(['team_id' => $other->current_team_id])));

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('notifications.read', $other->notifications()->sole()->id))
        ->assertNotFound();
});
