<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\MentionedInCommentNotification;
use App\Comment\Notifications\TicketCommentedNotification;
use App\Favorite\FavoriteEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

function teammateOf(UserEloquentModel $user): UserEloquentModel
{
    $teammate = UserEloquentModel::factory()->create();
    TeamUserEloquentModel::query()->create(['team_id' => $user->current_team_id, 'user_id' => $teammate->id, 'role' => 'member']);

    return $teammate;
}

it('adds a comment and records it', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'number' => 3]);

    actingAs($user)
        ->from(route('tickets.index'))
        ->post(route('comments.store', $ticket->id), ['body' => '  Ordered a new toner.  '])
        ->assertRedirectToRoute('tickets.index');

    expect(CommentEloquentModel::query()->sole())
        ->body->toBe('Ordered a new toner.')
        ->user_id->toBe($user->id)
        ->team_id->toBe($user->current_team_id)
        ->and(ActivityEloquentModel::query()->where('type', ActivityTypeEnum::TicketCommented->value)->sole()->subject_id)->toBe($ticket->id);
});

it('tells mentioned people, and the other watchers, but not the author', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $assignee = teammateOf($user);
    $mentioned = teammateOf($user);
    $stargazer = teammateOf($user);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'assignee_id' => $assignee->id]);
    FavoriteEloquentModel::factory()->forFavoritable($ticket, $stargazer)->create();
    FavoriteEloquentModel::factory()->forFavoritable($ticket, $user)->create();

    actingAs($user)->post(route('comments.store', $ticket->id), ['body' => '@Sam can you check?', 'mentions' => [$mentioned->id, $user->id]]);

    Notification::assertSentTo($mentioned, MentionedInCommentNotification::class, fn ($notification): bool => $notification->toArray($mentioned)['excerpt'] === '@Sam can you check?');
    Notification::assertSentTo([$assignee, $stargazer], TicketCommentedNotification::class);
    Notification::assertNotSentTo($mentioned, TicketCommentedNotification::class);
    Notification::assertNothingSentTo($user);
});

it('rejects an empty comment and mentions outside the team', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('comments.store', $ticket->id), ['body' => '', 'mentions' => [UserEloquentModel::factory()->create()->id]])
        ->assertSessionHasErrors(['body' => 'Write a comment first.', 'mentions.0' => 'You can only mention people in your team.']);
});

it("returns not found for another team's ticket", function (): void {
    actingAs(UserEloquentModel::factory()->withPersonalTeam()->create())
        ->post(route('comments.store', TicketEloquentModel::factory()->create()->id), ['body' => 'Hi'])
        ->assertNotFound();
});
