<?php

declare(strict_types=1);

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\Actions\MarkConversationRead;
use App\Inbox\Events\InboxChanged;
use App\Message\MessageEloquentModel;
use App\User\UserEloquentModel;
use Illuminate\Support\Facades\Event;

use function Illuminate\Support\defer;

it("tells the reader's other tabs when reading clears unread messages", function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $conversation = ConversationEloquentModel::factory()->between($user, $sam)->create();
    MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id]);
    Event::fake([InboxChanged::class]);

    MarkConversationRead::forParticipant($conversation->id, $user->id);
    defer()->invoke();

    Event::assertDispatched(InboxChanged::class, fn (InboxChanged $event): bool => $event->userId === $user->id);
});

it('tells nobody when the conversation was already read', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $conversation = ConversationEloquentModel::factory()->between($user, $sam)->create();
    MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id]);
    MarkConversationRead::forParticipant($conversation->id, $user->id);
    defer()->invoke();
    Event::fake([InboxChanged::class]);

    MarkConversationRead::forParticipant($conversation->id, $user->id);
    defer()->invoke();

    Event::assertNotDispatched(InboxChanged::class);
});
