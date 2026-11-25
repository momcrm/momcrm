<?php

declare(strict_types=1);

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\Message\MessageEloquentModel;
use App\Message\Notifications\MessageReceivedNotification;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

it('starts a conversation with the first message, and tells the others', function (): void {
    Notification::fake();
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $kim = messagingTeammate($user);

    $response = actingAs($user)->post(route('conversations.store'), ['recipients' => [$sam->id, $kim->id], 'body' => '  Coffee at 3?  ']);

    $conversation = ConversationEloquentModel::query()->withoutGlobalScopes()->sole();
    $response->assertRedirectToRoute('messages.index', ['conversation' => $conversation->id]);
    expect($conversation->team_id)->toBe($user->current_team_id)
        ->and($conversation->last_message_at)->not->toBeNull()
        ->and(ConversationParticipantEloquentModel::query()->pluck('user_id')->sort()->values()->all())->toBe(collect([$user->id, $sam->id, $kim->id])->sort()->values()->all())
        ->and(MessageEloquentModel::query()->withoutGlobalScopes()->sole())
        ->body->toBe('Coffee at 3?')
        ->user_id->toBe($user->id)
        ->conversation_id->toBe($conversation->id);
    Notification::assertSentTo([$sam, $kim], MessageReceivedNotification::class);
    Notification::assertNotSentTo($user, MessageReceivedNotification::class);
});

it('adds to the conversation with exactly those people, if there is one', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $kim = messagingTeammate($user);
    $withSam = ConversationEloquentModel::factory()->between($user, $sam)->create();
    ConversationEloquentModel::factory()->between($user, $sam, $kim)->create();

    actingAs($user)
        ->post(route('conversations.store'), ['recipients' => [$sam->id], 'body' => 'Hi again'])
        ->assertRedirectToRoute('messages.index', ['conversation' => $withSam->id]);

    expect(ConversationEloquentModel::query()->withoutGlobalScopes()->count())->toBe(2)
        ->and(MessageEloquentModel::query()->withoutGlobalScopes()->sole()->conversation_id)->toBe($withSam->id);
});

it('starts a new conversation when the people differ', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $kim = messagingTeammate($user);
    ConversationEloquentModel::factory()->between($user, $sam)->create();

    actingAs($user)->post(route('conversations.store'), ['recipients' => [$sam->id, $kim->id], 'body' => 'All three of us']);

    expect(ConversationEloquentModel::query()->withoutGlobalScopes()->count())->toBe(2);
});

it('refuses a message to nobody, to yourself or to someone outside the team, and saves nothing', function (Closure $recipientsFor, array $errors): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    actingAs($user)
        ->post(route('conversations.store'), ['recipients' => $recipientsFor($user), 'body' => ''])
        ->assertSessionHasErrors(['body' => 'Write a message first.', ...$errors]);

    expect(ConversationEloquentModel::query()->withoutGlobalScopes()->exists())->toBeFalse();
})->with([
    'nobody' => [fn (): array => [], ['recipients' => 'Choose who to send it to.']],
    'yourself' => [fn (UserEloquentModel $user): array => [$user->id], ['recipients.0' => 'Choose someone other than yourself.']],
    'outside the team' => [fn (): array => [UserEloquentModel::factory()->create()->id], ['recipients.0' => 'You can only message people in your team.']],
]);
