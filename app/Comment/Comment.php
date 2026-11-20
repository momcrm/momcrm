<?php

declare(strict_types=1);

namespace App\Comment;

use App\Comment\Factories\CommentFactory;
use App\Comment\Properties\Body;
use App\Comment\Properties\ContactId;
use App\Comment\Properties\CreatedAt;
use App\Comment\Properties\Id;
use App\Comment\Properties\Mentions;
use App\Comment\Properties\RecordId;
use App\Comment\Properties\SenderEmail;
use App\Comment\Properties\SenderName;
use App\Comment\Properties\TeamId;
use App\Comment\Properties\TicketId;
use App\Comment\Properties\UpdatedAt;
use App\Comment\Properties\UserId;
use App\Comment\Properties\Visibility;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<CommentEloquentModel>
 */
final class Comment extends AbstractData
{
    public function __construct(
        public Body $body,
        public ContactId $contactId,
        public CreatedAt $createdAt,
        public Id $id,
        public Mentions $mentions,
        public RecordId $recordId,
        public SenderEmail $senderEmail,
        public SenderName $senderName,
        public TeamId $teamId,
        public TicketId $ticketId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
        public Visibility $visibility,
    ) {}

    public static function getFactory(): CommentFactory
    {
        return CommentEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): CommentCollection
    {
        if ($items instanceof CommentCollection) {
            return $items;
        }

        return CommentCollection::make(parent::collect($items, $method)->all());
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

    public function getContactId(): ContactId
    {
        return $this->contactId;
    }

    public function setContactId(mixed $contactId): self
    {
        $this->contactId = new ContactId($contactId instanceof AbstractValue ? $contactId->toValue() : $contactId);

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

    public function getRecordId(): RecordId
    {
        return $this->recordId;
    }

    public function setRecordId(mixed $recordId): self
    {
        $this->recordId = new RecordId($recordId instanceof AbstractValue ? $recordId->toValue() : $recordId);

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

    public function getSenderEmail(): SenderEmail
    {
        return $this->senderEmail;
    }

    public function setSenderEmail(mixed $senderEmail): self
    {
        $this->senderEmail = new SenderEmail($senderEmail instanceof AbstractValue ? $senderEmail->toValue() : $senderEmail);

        return $this;
    }

    public function getSenderName(): SenderName
    {
        return $this->senderName;
    }

    public function setSenderName(mixed $senderName): self
    {
        $this->senderName = new SenderName($senderName instanceof AbstractValue ? $senderName->toValue() : $senderName);

        return $this;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function setVisibility(mixed $visibility): self
    {
        $this->visibility = new Visibility($visibility instanceof AbstractValue ? $visibility->toValue() : $visibility);

        return $this;
    }
}
