<?php

declare(strict_types=1);

namespace App\Conversation;

use App\Conversation\Factories\ConversationFactory;
use App\Conversation\Properties\CreatedAt;
use App\Conversation\Properties\Id;
use App\Conversation\Properties\LastMessageAt;
use App\Conversation\Properties\TeamId;
use App\Conversation\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A private conversation between two or more members of a team.
 *
 * @extends AbstractData<ConversationEloquentModel>
 */
final class Conversation extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Id $id,
        public LastMessageAt $lastMessageAt,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ConversationFactory
    {
        return ConversationEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ConversationCollection
    {
        if ($items instanceof ConversationCollection) {
            return $items;
        }

        return ConversationCollection::make(parent::collect($items, $method)->all());
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

    public function getLastMessageAt(): LastMessageAt
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(mixed $lastMessageAt): self
    {
        $this->lastMessageAt = new LastMessageAt($lastMessageAt instanceof AbstractValue ? $lastMessageAt->toValue() : $lastMessageAt);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
