<?php

declare(strict_types=1);

use App\Inbox\Events\InboxChanged;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Event;

use function Illuminate\Support\defer;
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

it("tells the person's other tabs after clearing a notification", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $user->notify(new TicketDueSoonNotification(TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id])));
    defer()->invoke();
    Event::fake([InboxChanged::class]);

    actingAs($user)->delete(route('notifications.destroy', $user->notifications()->sole()->id));

    Event::assertDispatched(InboxChanged::class, fn (InboxChanged $event): bool => $event->userId === $user->id);
});
