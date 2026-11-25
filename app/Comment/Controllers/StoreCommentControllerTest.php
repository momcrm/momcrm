<?php

declare(strict_types=1);

use App\Activity\ActivityEloquentModel;
use App\Activity\ActivityTypeEnum;
use App\Comment\CommentEloquentModel;
use App\Comment\Notifications\MentionedInCommentNotification;
use App\Comment\Notifications\TicketCommentedNotification;
use App\Comment\Notifications\TicketReplyInPortalNotification;
use App\Favorite\FavoriteEloquentModel;
use App\Mailbox\MailboxEloquentModel;
use App\MailboxMessage\Jobs\SendMailboxMessageJob;
use App\MailboxMessage\MailboxMessageEloquentModel;
use App\TeamUser\TeamUserEloquentModel;
use App\Ticket\TicketEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

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

it('does not tell the team about a teammate comment on a ticket nobody is assigned', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $teammate = teammateOf($user);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'assignee_id' => null]);
    Notification::fake();

    actingAs($user)->post(route('comments.store', $ticket->id), ['body' => 'Looking into it.']);

    Notification::assertNotSentTo($teammate, TicketCommentedNotification::class);
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

it('keeps a comment to the team unless it is sent as a reply', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $user->current_team_id]);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'mailbox_id' => $mailbox->id, 'requester_email' => 'sam@acme.test']);
    Queue::fake([SendMailboxMessageJob::class]);

    actingAs($user)->post(route('comments.store', $ticket->id), ['body' => 'Toner is on order.'])->assertSessionHasNoErrors();
    actingAs($user)->post(route('comments.store', $ticket->id), ['body' => 'Waiting on the supplier.', 'visibility' => 'internal'])->assertSessionHasNoErrors();

    expect(CommentEloquentModel::query()->pluck('visibility')->all())->toBe(['internal', 'internal'])
        ->and(MailboxMessageEloquentModel::query()->exists())->toBeFalse();

    Queue::assertNotPushed(SendMailboxMessageJob::class);
});

it('lines up a reply to be emailed to whoever wrote in, from the mailbox the ticket came in through', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    MailboxEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'address' => 'accounts@helpdesk.test']);
    $mailbox = MailboxEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'address' => 'support@helpdesk.test']);
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id, 'mailbox_id' => $mailbox->id, 'requester_email' => 'sam@acme.test']);
    Queue::fake([SendMailboxMessageJob::class]);

    actingAs($user)
        ->post(route('comments.store', $ticket->id), ['body' => 'A new toner arrives tomorrow.', 'visibility' => 'public'])
        ->assertSessionHasNoErrors();

    $comment = CommentEloquentModel::query()->sole();
    $message = MailboxMessageEloquentModel::query()->sole();

    expect($comment)
        ->visibility->toBe('public')
        ->user_id->toBe($user->id)
        ->sender_email->toBeNull()
        ->and($message)
        ->team_id->toBe($user->current_team_id)
        ->mailbox_id->toBe($mailbox->id)
        ->direction->toBe('outbound')
        ->status->toBe('pending')
        ->from_address->toBe('support@helpdesk.test')
        ->to_recipients->toBe(['sam@acme.test'])
        ->body_text->toBe('A new toner arrives tomorrow.')
        ->ticket_id->toBe($ticket->id)
        ->comment_id->toBe($comment->id);

    Queue::assertPushedTimes(SendMailboxMessageJob::class, 1);
    Queue::assertPushed(fn (SendMailboxMessageJob $job): bool => $job->messageId === $message->id);
});

it('refuses a reply that cannot be sent, and saves nothing', function (Closure $ticketFor, string $message): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = $ticketFor($user->current_team_id);
    Queue::fake([SendMailboxMessageJob::class]);

    actingAs($user)
        ->post(route('comments.store', $ticket->id), ['body' => 'A new toner arrives tomorrow.', 'visibility' => 'public'])
        ->assertSessionHasErrors(['visibility' => $message]);

    expect(CommentEloquentModel::query()->exists())->toBeFalse();

    Queue::assertNotPushed(SendMailboxMessageJob::class);
})->with([
    'the ticket has nobody to write to' => [
        function (string $teamId) {
            MailboxEloquentModel::factory()->create(['team_id' => $teamId]);

            return TicketEloquentModel::factory()->create(['team_id' => $teamId]);
        },
        'This ticket has no email address to reply to.',
    ],
    'the team has no mailbox it can send from' => [
        function (string $teamId) {
            MailboxEloquentModel::factory()->needsReconnect()->create(['team_id' => $teamId]);
            MailboxEloquentModel::factory()->create();

            return TicketEloquentModel::factory()->create(['team_id' => $teamId, 'requester_email' => 'sam@acme.test']);
        },
        'Connect a mailbox in Settings → Email before replying to customers.',
    ],
]);

it('rejects a visibility it does not know', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $ticket = TicketEloquentModel::factory()->create(['team_id' => $user->current_team_id]);

    actingAs($user)
        ->post(route('comments.store', $ticket->id), ['body' => 'Hello', 'visibility' => 'everyone'])
        ->assertSessionHasErrors(['visibility' => 'The selected visibility is invalid.']);
});

it("tells a portal contact about a reply when there's no mailbox to send it from", function (): void {
    Notification::fake();
    $contact = portalContact();
    $staff = UserEloquentModel::query()->where('current_team_id', $contact->team_id)->sole();
    $ticket = portalTicket($contact, ['contact_id' => $contact->id, 'title' => 'Printer jam']);

    actingAs($staff)
        ->post(route('comments.store', $ticket->id), ['body' => 'We have ordered a part.', 'visibility' => 'public'])
        ->assertSessionHasNoErrors();

    expect(CommentEloquentModel::query()->withoutGlobalScopes()->sole()->visibility)->toBe('public');
    Notification::assertSentOnDemand(
        TicketReplyInPortalNotification::class,
        fn (TicketReplyInPortalNotification $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'mark@coastal.test'
            && $notification->body === 'We have ordered a part.',
    );
    expect(MailboxMessageEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});
