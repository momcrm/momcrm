<?php

declare(strict_types=1);

use App\Inbox\Events\InboxChanged;
use App\Ticket\Notifications\TicketDueSoonNotification;
use App\Ticket\TicketEloquentModel;
use App\User\Notifications\WelcomeNotification;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Event;

use function Illuminate\Support\defer;

it("tells the person's open pages when a notification reaches their inbox", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    defer()->invoke();
    Event::fake([InboxChanged::class]);

    $user->notify(new TicketDueSoonNotification($ticket));
    defer()->invoke();

    Event::assertDispatched(InboxChanged::class, fn (InboxChanged $event): bool => $event->userId === $user->id);
});

it("says nothing about a notification that isn't shown in the inbox", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    defer()->invoke();
    Event::fake([InboxChanged::class]);

    $user->notify(new WelcomeNotification);
    defer()->invoke();

    Event::assertNotDispatched(InboxChanged::class);
});
