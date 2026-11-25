<?php

declare(strict_types=1);

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\Message\MessageEloquentModel;
use App\User\UserEloquentModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

it("lists the person's conversations, latest first, with who is in them and what they haven't read", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user, ['name' => 'Sam Lee']);
    $kim = messagingTeammate($user, ['name' => 'Kim Ng']);
    $withSam = ConversationEloquentModel::factory()->between($user, $sam)->create(['last_message_at' => '2026-11-24 09:00:00']);
    $withBoth = ConversationEloquentModel::factory()->between($user, $sam, $kim)->create(['last_message_at' => '2026-11-25 09:00:00']);
    ConversationEloquentModel::factory()->between($sam, $kim)->create(['last_message_at' => '2026-11-25 10:00:00']);
    MessageEloquentModel::factory()->create(['conversation_id' => $withSam->id, 'user_id' => $sam->id, 'body' => 'Lunch?', 'created_at' => '2026-11-24 08:00:00']);
    MessageEloquentModel::factory()->create(['conversation_id' => $withSam->id, 'user_id' => $sam->id, 'body' => 'Still on?', 'created_at' => '2026-11-24 09:00:00']);
    MessageEloquentModel::factory()->create(['conversation_id' => $withBoth->id, 'user_id' => $user->id, 'body' => 'Morning all', 'created_at' => '2026-11-25 09:00:00']);

    actingAs($user)
        ->get(route('messages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('messages')
            ->has('conversations', 2)
            ->where('conversations.0.id', $withBoth->id)
            ->where('conversations.0.participants', [['id' => $kim->id, 'name' => 'Kim Ng'], ['id' => $sam->id, 'name' => 'Sam Lee']])
            ->where('conversations.0.last_message.body', 'Morning all')
            ->where('conversations.0.unread', 0)
            ->where('conversations.1.last_message.body', 'Still on?')
            ->where('conversations.1.unread', 2)
            ->where('sidebarCounts.unreadMessages', 2)
            ->where('selected', null)
            ->where('members', [['id' => $kim->id, 'name' => 'Kim Ng'], ['id' => $sam->id, 'name' => 'Sam Lee']]),
        );
});

it('opens a conversation and marks it read', function (): void {
    travelTo('2026-11-25 10:00:00');
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user, ['name' => 'Sam Lee']);
    $conversation = ConversationEloquentModel::factory()->between($user, $sam)->create(['last_message_at' => '2026-11-25 09:00:00']);
    MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user->id, 'body' => 'Lunch?', 'created_at' => '2026-11-25 08:00:00']);
    MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id, 'body' => 'Yes!', 'created_at' => '2026-11-25 09:00:00']);

    actingAs($user)
        ->get(route('messages.index', ['conversation' => $conversation->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.id', $conversation->id)
            ->where('selected.participants', [['id' => $sam->id, 'name' => 'Sam Lee']])
            ->where('selected.messages', fn ($messages): bool => collect($messages)->pluck('body')->all() === ['Lunch?', 'Yes!'])
            ->where('selected.messages.1.author_name', 'Sam Lee')
            ->where('conversations.0.unread', 0)
            ->where('sidebarCounts.unreadMessages', 0),
        );

    expect(ConversationParticipantEloquentModel::query()->where('user_id', $user->id)->sole()->last_read_at?->toDateTimeString())->toBe('2026-11-25 10:00:00')
        ->and(ConversationParticipantEloquentModel::query()->where('user_id', $sam->id)->sole()->last_read_at)->toBeNull();
});

it("doesn't open a conversation the person isn't in", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $kim = messagingTeammate($user);
    $theirs = ConversationEloquentModel::factory()->between($sam, $kim)->create(['last_message_at' => now()]);
    MessageEloquentModel::factory()->create(['conversation_id' => $theirs->id, 'user_id' => $sam->id]);

    actingAs($user)
        ->get(route('messages.index', ['conversation' => $theirs->id]))
        ->assertInertia(fn (Assert $page) => $page->where('selected', null)->has('conversations', 0));

    expect(ConversationParticipantEloquentModel::query()->whereNotNull('last_read_at')->exists())->toBeFalse();
});

it('asks a guest to sign in', function (): void {
    get(route('messages.index'))->assertRedirectToRoute('login');
});

it('reloads the conversations and the open one when the inbox changes', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();

    $response = actingAs($user)->get(route('messages.index'))->assertOk();

    expect(liveListeners($response, 'conversations'))->toBe(["private-inbox.{$user->id} inbox.changed"])
        ->and(liveListeners($response, 'selected'))->toBe(["private-inbox.{$user->id} inbox.changed"]);
});
