<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Factories\TicketFactory;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CreatedAt;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\Priority;
use App\Ticket\Properties\Status;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\Properties\UpdatedAt;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<TicketEloquentModel>
 */
final class Ticket extends AbstractData
{
    public function __construct(
        public AssigneeId $assigneeId,
        public ClosedAt $closedAt,
        public ContactId $contactId,
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Description $description,
        public Id $id,
        public LocationId $locationId,
        public Number $number,
        public Priority $priority,
        public Status $status,
        public TeamId $teamId,
        public Title $title,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): TicketFactory
    {
        return TicketEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    public function isOpen(): bool
    {
        return $this->getStatus()->toEnum()->isOpen();
    }

    /**
     * The fields the ticket pages show.
     *
     * @return array{id: string, number: int, title: string, description: ?string, status: string, priority: string, customer_id: string, contact_id: ?string, location_id: ?string, assignee_id: ?string, closed_at: ?string, created_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'title' => $this->getTitle()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'status' => $this->getStatus()->toString(),
            'priority' => $this->getPriority()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'contact_id' => $this->getContactId()->toNullableString(),
            'location_id' => $this->getLocationId()->toNullableString(),
            'assignee_id' => $this->getAssigneeId()->toNullableString(),
            'closed_at' => $this->getClosedAt()->toNullableISOString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    public function getAssigneeId(): AssigneeId
    {
        return $this->assigneeId;
    }

    public function setAssigneeId(mixed $assigneeId): self
    {
        $this->assigneeId = new AssigneeId($assigneeId instanceof AbstractValue ? $assigneeId->toValue() : $assigneeId);

        return $this;
    }

    public function getClosedAt(): ClosedAt
    {
        return $this->closedAt;
    }

    public function setClosedAt(mixed $closedAt): self
    {
        $this->closedAt = new ClosedAt($closedAt instanceof AbstractValue ? $closedAt->toValue() : $closedAt);

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

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function setCustomerId(mixed $customerId): self
    {
        $this->customerId = new CustomerId($customerId instanceof AbstractValue ? $customerId->toValue() : $customerId);

        return $this;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

        return $this;
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

    public function getLocationId(): LocationId
    {
        return $this->locationId;
    }

    public function setLocationId(mixed $locationId): self
    {
        $this->locationId = new LocationId($locationId instanceof AbstractValue ? $locationId->toValue() : $locationId);

        return $this;
    }

    public function getNumber(): Number
    {
        return $this->number;
    }

    public function setNumber(mixed $number): self
    {
        $this->number = new Number($number instanceof AbstractValue ? $number->toValue() : $number);

        return $this;
    }

    public function getPriority(): Priority
    {
        return $this->priority;
    }

    public function setPriority(mixed $priority): self
    {
        $this->priority = new Priority($priority instanceof AbstractValue ? $priority->toValue() : $priority);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

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

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(mixed $title): self
    {
        $this->title = new Title($title instanceof AbstractValue ? $title->toValue() : $title);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
