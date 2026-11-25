<?php

declare(strict_types=1);

namespace App\Message;

use App\Message\Factories\MessageFactory;
use App\Message\Properties\Body;
use App\Message\Properties\ConversationId;
use App\Message\Properties\CreatedAt;
use App\Message\Properties\Id;
use App\Message\Properties\TeamId;
use App\Message\Properties\UpdatedAt;
use App\Message\Properties\UserId;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One message in a conversation.
 *
 * @extends AbstractData<MessageEloquentModel>
 */
final class Message extends AbstractData
{
    public function __construct(
        public Body $body,
        public ConversationId $conversationId,
        public CreatedAt $createdAt,
        public Id $id,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): MessageFactory
    {
        return MessageEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): MessageCollection
    {
        if ($items instanceof MessageCollection) {
            return $items;
        }

        return MessageCollection::make(parent::collect($items, $method)->all());
    }

    public function getBody(): Body
    {
        return $this->body;
    }

    public function setBody(mixed $body): self
    {
        $this->body = new Body($body instanceof AbstractValue ? $body->toValue() : $body);

        return $this;
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
