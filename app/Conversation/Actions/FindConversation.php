<?php

declare(strict_types=1);

namespace App\Conversation\Actions;

use App\Conversation\Conversation;
use App\Conversation\ConversationEloquentModel;
use App\Conversation\ConversationRelationship;
use App\Conversation\Properties\Id;
use App\Conversation\Properties\LastMessageAt;
use App\Conversation\Properties\TeamId;
use App\ConversationParticipant\ConversationParticipantEloquentModel;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\LastReadAt;
use App\ConversationParticipant\Properties\UserId;
use App\Message\MessageEloquentModel;
use App\Message\Properties\Body;
use App\Message\Properties\ConversationId as MessageConversationId;
use App\Message\Properties\CreatedAt as MessageCreatedAt;
use App\Message\Properties\Id as MessageId;
use App\Message\Properties\TeamId as MessageTeamId;
use App\Message\Properties\UserId as MessageUserId;
use App\Shared\Table;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Str;

/**
 * Someone's direct-message conversations in a team.
 */
final class FindConversation
{
    public const int LIMIT = 100;

    /**
     * The conversation, if the person is in it.
     */
    public static function byIdForParticipant(?string $id, ?string $teamId, string $userId): Conversation
    {
        if ($id === null || $teamId === null || ! Str::isUuid($id)) {
            return Conversation::new();
        }

        return Conversation::fromEloquentModel(self::forParticipant($teamId, $userId)->whereKey($id)->first());
    }

    /**
     * The conversation between exactly these people, if they have one.
     *
     * @param  list<string>  $userIds  everyone in it, including whoever asks
     */
    public static function betweenExactly(string $teamId, array $userIds): Conversation
    {
        $userIds = array_values(array_unique($userIds));

        if ($userIds === []) {
            return Conversation::new();
        }

        $query = self::forParticipant($teamId, $userIds[0])->has(ConversationRelationship::Participants->value, '=', count($userIds));

        foreach (array_slice($userIds, 1) as $userId) {
            $query->whereHas(ConversationRelationship::Participants->value, fn (Builder $participants) => $participants->where(UserId::getDatabaseTableColumnName(), $userId));
        }

        return Conversation::fromEloquentModel($query->first());
    }

    /**
     * The person's conversations, latest message first, each with the other
     * people in it, its latest message and how many messages they haven't read.
     *
     * @return list<array{id: string, participants: list<array{id: string, name: string}>, last_message: array{body: string, author_id: ?string, created_at: ?string}|null, last_message_at: ?string, unread: int}>
     */
    public static function listForUser(string $teamId, string $userId): array
    {
        $conversations = self::forParticipant($teamId, $userId)
            ->whereNotNull(LastMessageAt::getDatabaseTableColumnName())
            ->with(ConversationRelationship::Participants->value)
            ->orderByDesc(LastMessageAt::getDatabaseTableColumnName())
            ->limit(self::LIMIT)
            ->get();

        $ids = Types::stringList($conversations->pluck(Id::getDatabaseTableColumnName()));
        $names = self::namesOf(Types::stringList($conversations->flatMap(fn (ConversationEloquentModel $conversation) => $conversation->participants->pluck(UserId::getDatabaseTableColumnName()))->unique()->values()));
        $unread = self::unreadCounts($teamId, $userId, $ids);

        // Message ids are UUIDv7, so the largest in a conversation is its latest.
        $latest = MessageEloquentModel::query()
            ->withoutGlobalScopes()
            ->whereIn(MessageId::getDatabaseTableColumnName(), MessageEloquentModel::query()
                ->withoutGlobalScopes()
                ->selectRaw('max(id)')
                ->whereIn(MessageConversationId::getDatabaseTableColumnName(), $ids)
                ->groupBy(MessageConversationId::getDatabaseTableColumnName()))
            ->get()
            ->keyBy(MessageConversationId::getDatabaseTableColumnName());

        return array_values($conversations->map(function (ConversationEloquentModel $conversation) use ($userId, $names, $unread, $latest): array {
            $message = $latest->get($conversation->id);

            return [
                'id' => $conversation->id,
                'participants' => self::others($conversation, $userId, $names),
                'last_message' => $message instanceof MessageEloquentModel ? [
                    'body' => Str::limit($message->body, 140),
                    'author_id' => $message->user_id,
                    'created_at' => $message->created_at?->toIso8601String(),
                ] : null,
                'last_message_at' => $conversation->last_message_at?->toIso8601String(),
                'unread' => Types::intOrZero($unread[$conversation->id] ?? 0),
            ];
        })->all());
    }

    /**
     * The people in a conversation other than this person.
     *
     * @return list<array{id: string, name: string}>
     */
    public static function othersIn(string $conversationId, string $userId): array
    {
        $userIds = ConversationParticipantEloquentModel::query()
            ->where(ConversationId::getDatabaseTableColumnName(), $conversationId)
            ->where(UserId::getDatabaseTableColumnName(), '!=', $userId)
            ->pluck(UserId::getDatabaseTableColumnName())
            ->all();

        $names = self::namesOf(Types::stringList($userIds));
        $people = array_map(fn (string $id, string $name): array => ['id' => $id, 'name' => $name], array_keys($names), array_values($names));
        usort($people, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $people;
    }

    /**
     * Messages others sent the person that they haven't read yet, across all
     * their conversations in the team.
     */
    public static function countUnreadMessages(string $teamId, string $userId): int
    {
        return self::unread($teamId, $userId)->count();
    }

    /**
     * Whether others sent the person messages in one conversation that they
     * haven't read yet.
     */
    public static function hasUnreadMessagesIn(string $conversationId, string $userId): bool
    {
        return self::unreadAcrossTeams($userId)
            ->where(MessageConversationId::getDatabaseTableColumnNameWithTable(Table::Messages), $conversationId)
            ->exists();
    }

    /**
     * @return Builder<ConversationEloquentModel>
     */
    private static function forParticipant(string $teamId, string $userId): Builder
    {
        return ConversationEloquentModel::query()
            ->withoutGlobalScopes()
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->whereHas(ConversationRelationship::Participants->value, fn (Builder $query) => $query->where(UserId::getDatabaseTableColumnName(), $userId));
    }

    /**
     * @return Builder<MessageEloquentModel>
     */
    private static function unread(string $teamId, string $userId): Builder
    {
        return self::unreadAcrossTeams($userId)
            ->where(MessageTeamId::getDatabaseTableColumnNameWithTable(Table::Messages), $teamId);
    }

    /**
     * @return Builder<MessageEloquentModel>
     */
    private static function unreadAcrossTeams(string $userId): Builder
    {
        $messages = Table::Messages;
        $lastReadAt = 'me.'.LastReadAt::getDatabaseTableColumnName();

        return MessageEloquentModel::query()
            ->withoutGlobalScopes()
            ->join(Table::ConversationParticipants->value.' as me', fn (JoinClause $join) => $join
                ->on('me.'.ConversationId::getDatabaseTableColumnName(), '=', MessageConversationId::getDatabaseTableColumnNameWithTable($messages))
                ->where('me.'.UserId::getDatabaseTableColumnName(), '=', $userId))
            ->where(fn (Builder $query) => $query
                ->whereNull(MessageUserId::getDatabaseTableColumnNameWithTable($messages))
                ->orWhere(MessageUserId::getDatabaseTableColumnNameWithTable($messages), '!=', $userId))
            ->where(fn (Builder $query) => $query
                ->whereNull($lastReadAt)
                ->orWhereColumn(MessageCreatedAt::getDatabaseTableColumnNameWithTable($messages), '>', $lastReadAt));
    }

    /**
     * @param  list<string>  $conversationIds
     * @return array<string, int>
     */
    private static function unreadCounts(string $teamId, string $userId, array $conversationIds): array
    {
        $column = MessageConversationId::getDatabaseTableColumnNameWithTable(Table::Messages);

        return self::unread($teamId, $userId)
            ->whereIn($column, $conversationIds)
            ->groupBy($column)
            ->select($column)
            ->selectRaw('count(*) as aggregate')
            ->get()
            ->mapWithKeys(fn (MessageEloquentModel $row): array => [Types::string($row->getAttribute(MessageConversationId::getDatabaseTableColumnName())) => Types::intOrZero($row->getAttribute('aggregate'))])
            ->all();
    }

    /**
     * @param  list<string>  $userIds
     * @return array<string, string>
     */
    private static function namesOf(array $userIds): array
    {
        return UserEloquentModel::query()
            ->whereKey($userIds)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (UserEloquentModel $user): array => [$user->id => $user->name])
            ->all();
    }

    /**
     * @param  array<string, string>  $names
     * @return list<array{id: string, name: string}>
     */
    private static function others(ConversationEloquentModel $conversation, string $userId, array $names): array
    {
        return array_values($conversation->participants
            ->reject(fn (ConversationParticipantEloquentModel $participant): bool => $participant->user_id === $userId || ! isset($names[$participant->user_id]))
            ->map(fn (ConversationParticipantEloquentModel $participant): array => ['id' => $participant->user_id, 'name' => $names[$participant->user_id]])
            ->sortBy('name')
            ->values()
            ->all());
    }
}
