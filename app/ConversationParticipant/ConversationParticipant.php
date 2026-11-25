<?php

declare(strict_types=1);

namespace App\ConversationParticipant;

use App\ConversationParticipant\Factories\ConversationParticipantFactory;
use App\ConversationParticipant\Properties\ConversationId;
use App\ConversationParticipant\Properties\CreatedAt;
use App\ConversationParticipant\Properties\Id;
use App\ConversationParticipant\Properties\LastReadAt;
use App\ConversationParticipant\Properties\UpdatedAt;
use App\ConversationParticipant\Properties\UserId;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * Someone in a conversation, and how far they have read it.
 *
 * @extends AbstractData<ConversationParticipantEloquentModel>
 */
final class ConversationParticipant extends AbstractData
{
    public function __construct(
        public ConversationId $conversationId,
        public CreatedAt $createdAt,
        public Id $id,
        public LastReadAt $lastReadAt,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): ConversationParticipantFactory
    {
        return ConversationParticipantEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ConversationParticipantCollection
    {
        if ($items instanceof ConversationParticipantCollection) {
            return $items;
        }

        return ConversationParticipantCollection::make(parent::collect($items, $method)->all());
    }

    public function getConversationId(): ConversationId
    {
        return $this->conversationId;
    }

    public function setConversationId(mixed $conversationId): self
    {
        $this->conversationId = new ConversationId($conversationId instanceof AbstractValue ? $conversationId->toValue() : $conversationId);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getLastReadAt(): LastReadAt
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(mixed $lastReadAt): self
    {
        $this->lastReadAt = new LastReadAt($lastReadAt instanceof AbstractValue ? $lastReadAt->toValue() : $lastReadAt);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function setUserId(mixed $userId): self
    {
        $this->userId = new UserId($userId instanceof AbstractValue ? $userId->toValue() : $userId);

        return $this;
    }
}
