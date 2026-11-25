<?php

declare(strict_types=1);

namespace App\ConversationParticipant\Actions;

use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\LastReadAt;
use App\ConversationParticipant\Properties\UserId;

/**
 * Notes that someone has read a conversation up to now.
 */
final readonly class MarkConversationRead
{
    public function __construct(
        private string $conversationId,
        private string $userId,
    ) {}

    public function __invoke(): void
    {
        ConversationParticipantEloquentModel::query()
            ->where(ConversationId::getDatabaseTableColumnName(), $this->conversationId)
            ->where(UserId::getDatabaseTableColumnName(), $this->userId)
            ->update([LastReadAt::getDatabaseTableColumnName() => now()]);
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
