<?php

declare(strict_types=1);

namespace App\Comment;

use App\Comment\Factories\CommentFactory;
use App\Comment\Properties\Body;
use App\Comment\Properties\CreatedAt;
use App\Comment\Properties\Id;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\TeamId;
use App\Comment\Properties\TicketId;
use App\Comment\Properties\UpdatedAt;
use App\Comment\Properties\UserId;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<CommentEloquentModel>
 */
final class Comment extends AbstractData
{
    public function __construct(
        public Body $body,
        public CreatedAt $createdAt,
        public Id $id,
        public Mentions $mentions,
        public TeamId $teamId,
        public TicketId $ticketId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): CommentFactory
    {
        return CommentEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
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

    public function getMentions(): Mentions
    {
        return $this->mentions;
    }

    public function setMentions(mixed $mentions): self
    {
        $this->mentions = new Mentions($mentions instanceof AbstractValue ? $mentions->toValue() : $mentions);

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

    public function getTicketId(): TicketId
    {
        return $this->ticketId;
    }

    public function setTicketId(mixed $ticketId): self
    {
        $this->ticketId = new TicketId($ticketId instanceof AbstractValue ? $ticketId->toValue() : $ticketId);

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
