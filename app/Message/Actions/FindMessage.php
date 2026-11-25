<?php

declare(strict_types=1);

namespace App\Message\Actions;

use App\Message\MessageEloquentModel;
use App\Message\MessageRelationship;
use App\Message\Properties\ConversationId;
use App\Message\Properties\CreatedAt;
use App\Message\Properties\Id;
use App\User\UserEloquentModel;

/**
 * The messages in a conversation.
 */
final class FindMessage
{
    public const int LIMIT = 200;

    /**
     * The latest messages, oldest first, as a conversation reads.
     *
     * @return list<array{id: string, body: string, author_id: ?string, author_name: ?string, created_at: ?string}>
     */
    public static function listForConversation(string $conversationId): array
    {
        return array_values(MessageEloquentModel::query()
            ->withoutGlobalScopes()
            ->with(MessageRelationship::Author->value)
            ->where(ConversationId::getDatabaseTableColumnName(), $conversationId)
            ->orderByDesc(CreatedAt::getDatabaseTableColumnName())
            ->orderByDesc(Id::getDatabaseTableColumnName())
            ->limit(self::LIMIT)
            ->get()
            ->reverse()
            ->map(function (MessageEloquentModel $message): array {
                $author = $message->getRelationValue(MessageRelationship::Author->value);

                return [
                    'id' => $message->id,
                    'body' => $message->body,
                    'author_id' => $message->user_id,
                    'author_name' => $author instanceof UserEloquentModel ? $author->name : null,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->all());
    }
}
