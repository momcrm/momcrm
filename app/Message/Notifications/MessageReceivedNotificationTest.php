<?php

declare(strict_types=1);

use App\Conversation\ConversationEloquentModel;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\Message\MessageEloquentModel;
use App\Message\Notifications\MessageReceivedNotification;
use App\User\UserEloquentModel;

function messageNotification(MessageEloquentModel $message): MessageReceivedNotification
{
    return new MessageReceivedNotification($message->id, $message->conversation_id, 'Sam Lee', $message->body);
}

it('emails messages until the person chooses not to be', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $optedOut = UserEloquentModel::factory()->withPersonalTeam()->create(['emailed_notifications' => ['mention']]);
    $notification = new MessageReceivedNotification('id', 'conversation', 'Sam Lee', 'Hi');

    expect($notification->via($user))->toBe(['mail'])
        ->and($notification->via($optedOut))->toBe([])
        ->and($notification->delay)->not->toBeNull();
});

it('is sent only while the message is unread, and once per run of unread messages', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $sam = messagingTeammate($user);
    $conversation = ConversationEloquentModel::factory()->between($user, $sam)->create();
    $old = MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id, 'created_at' => '2026-11-25 08:00:00']);
    $first = MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id, 'created_at' => '2026-11-25 09:00:00']);
    $second = MessageEloquentModel::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $sam->id, 'created_at' => '2026-11-25 09:01:00']);
    ConversationParticipantEloquentModel::query()->where('user_id', $user->id)->update(['last_read_at' => '2026-11-25 08:30:00']);

    expect(messageNotification($old)->shouldSend($user, 'mail'))->toBeFalse()
        ->and(messageNotification($first)->shouldSend($user, 'mail'))->toBeTrue()
        ->and(messageNotification($second)->shouldSend($user, 'mail'))->toBeFalse();
});

it('says who wrote, shows the message as written, and links to the conversation', function (): void {
    $user = UserEloquentModel::factory()->withPersonalTeam()->create();
    $notification = new MessageReceivedNotification('id', 'conversation-id', 'Sam Lee', '[Click](https://evil.test) for the *agenda*');

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Sam Lee sent you a message')
        ->and($mail->actionUrl)->toBe(route('messages.index', ['conversation' => 'conversation-id']))
        ->and((string) $mail->render())
        ->not->toContain('href="https://evil.test"')
        ->not->toContain('<em>agenda</em>')
        ->toContain('[Click](https://evil.test) for the *agenda*');
});
