<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Favorite\FavoriteEloquentModel;
use App\Ticket\Actions\UpdateTicket;
use App\Ticket\Notifications\TicketAssignedNotification;
use App\Ticket\Notifications\TicketStatusChangedNotification;
use App\Ticket\Ticket;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('tells the new assignee, and records the assignment', function (): void {
    Notification::fake();
    $actor = UserEloquentModel::factory()->create();
    $assignee = UserEloquentModel::factory()->create();
    $model = TicketEloquentModel::factory()->create();

    UpdateTicket::fromTicket(Ticket::fromEloquentModel($model)->setAssigneeId($assignee->id), updatedById: $actor->id);

    Notification::assertSentTo($assignee, TicketAssignedNotification::class);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketAssigned->value)->sole()->details)->toMatchArray(['assignee' => $assignee->name]);
});

it('does not tell you about tickets you assign to yourself', function (): void {
    Notification::fake();
    $actor = UserEloquentModel::factory()->create();

    UpdateTicket::fromTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create())->setAssigneeId($actor->id), updatedById: $actor->id);

    Notification::assertNothingSent();
});

it("tells the watchers a ticket's status changed, but not whoever changed it", function (): void {
    Notification::fake();
    $actor = UserEloquentModel::factory()->create();
    $assignee = UserEloquentModel::factory()->create();
    $model = TicketEloquentModel::factory()->create(['assignee_id' => $assignee->id]);
    $stargazer = UserEloquentModel::factory()->create();
    FavoriteEloquentModel::factory()->forFavoritable($model, $stargazer)->create();
    FavoriteEloquentModel::factory()->forFavoritable($model, $actor)->create();

    UpdateTicket::fromTicket(Ticket::fromEloquentModel($model)->setStatus('in_progress'), updatedById: $actor->id);

    Notification::assertSentTo([$assignee, $stargazer], TicketStatusChangedNotification::class, fn ($notification): bool => $notification->statusLabel === 'In progress');
    Notification::assertNotSentTo($actor, TicketStatusChangedNotification::class);
    expect(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketStatusChanged->value)->sole()->details)
        ->toMatchArray(['from' => 'New', 'to' => 'In progress']);
});

it('sends notifications in the background, not during the request', function (): void {
    Queue::fake();
    $assignee = UserEloquentModel::factory()->create();

    UpdateTicket::fromTicket(Ticket::fromEloquentModel(TicketEloquentModel::factory()->create())->setAssigneeId($assignee->id), updatedById: UserEloquentModel::factory()->create()->id);

    Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof TicketAssignedNotification);
});
