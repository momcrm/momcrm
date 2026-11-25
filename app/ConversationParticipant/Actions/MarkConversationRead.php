<?php

declare(strict_types=1);

namespace App\ConversationParticipant\Actions;

use App\Conversation\Actions\FindConversation;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\LastReadAt;
use App\ConversationParticipant\Properties\UserId;
use App\Inbox\Events\InboxChanged;

/**
 * Notes that someone has read a conversation up to now. When that clears
 * unread messages, their other open tabs are told, so their counts drop too.
 * Reading a conversation already read tells nobody: the Messages page reads
 * the open conversation each time it reloads.
 */
final readonly class MarkConversationRead
{
    public function __construct(
        private string $conversationId,
        private string $userId,
    ) {}

    public function __invoke(): void
    {
        $hadUnread = FindConversation::hasUnreadMessagesIn($this->conversationId, $this->userId);

        ConversationParticipantEloquentModel::query()
            ->where(ConversationId::getDatabaseTableColumnName(), $this->conversationId)
            ->where(UserId::getDatabaseTableColumnName(), $this->userId)
            ->update([LastReadAt::getDatabaseTableColumnName() => now()]);

        if ($hadUnread) {
            InboxChanged::later($this->userId);
        }
    }

    public static function forParticipant(string $conversationId, string $userId): void
    {
        new self($conversationId, $userId)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
