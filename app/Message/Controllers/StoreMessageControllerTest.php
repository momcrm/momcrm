<?php

declare(strict_types=1);

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\Message\MessageEloquentModel;
use App\Message\Notifications\MessageReceivedNotification;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

it('adds the message, counts it read by the sender, and tells the others', function (): void {
    Notification::fake();
    travelTo('2026-11-25 10:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $conversation = ConversationEloquentModel::factory()->between($user, $sam)->create(['last_message_at' => '2026-11-24 09:00:00']);

    actingAs($user)
        ->post(route('messages.store', $conversation->id), ['body' => 'On my way'])
        ->assertRedirectToRoute('messages.index', ['conversation' => $conversation->id]);

    expect(MessageEloquentModel::query()->sole())
        ->body->toBe('On my way')
        ->user_id->toBe($user->id)
        ->team_id->toBe($user->current_team_id)
        ->and($conversation->refresh()->last_message_at?->toDateTimeString())->toBe('2026-11-25 10:00:00')
        ->and(ConversationParticipantEloquentModel::query()->where('user_id', $user->id)->sole()->last_read_at?->toDateTimeString())->toBe('2026-11-25 10:00:00');
    Notification::assertSentTo($sam, MessageReceivedNotification::class, fn (MessageReceivedNotification $notification): bool => $notification->body === 'On my way');
    Notification::assertNothingSentTo($user);
});

it("returns not found for a conversation the person isn't in", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $theirs = ConversationEloquentModel::factory()->between(messagingTeammate($user), messagingTeammate($user))->create();

    actingAs($user)
        ->post(route('messages.store', $theirs->id), ['body' => 'Hi'])
        ->assertNotFound();

    expect(MessageEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
});

it('refuses an empty message', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $conversation = ConversationEloquentModel::factory()->between($user, messagingTeammate($user))->create();

    actingAs($user)
        ->post(route('messages.store', $conversation->id), ['body' => ''])
        ->assertSessionHasErrors(['body' => 'Write a message first.']);
});
