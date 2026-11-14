<?php

declare(strict_types=1);

use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;

use function Pest\Laravel\actingAs;

it('clears a notification', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $user->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])));

    actingAs($user)->delete(route('notifications.destroy', $user->notifications()->sole()->id));

    expect($user->notifications()->exists())->toBeFalse();
});

it("returns not found for someone else's notification", function (): void {
    $other = UserEloquentModel::factory()->withPersonalTeam()->create();
    $other->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create(['team_id' => $other->current_team_id])));

    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->delete(route('notifications.destroy', $other->notifications()->sole()->id))
        ->assertNotFound();
});
