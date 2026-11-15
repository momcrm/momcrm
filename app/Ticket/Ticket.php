<?php

declare(strict_types=1);

namespace App\Ticket;

use App\Ticket\Factories\TicketFactory;
use App\Ticket\Properties\ArchivedAt;
use App\Ticket\Properties\Assignee;
use App\Ticket\Properties\AssigneeId;
use App\Ticket\Properties\ClosedAt;
use App\Ticket\Properties\ClosedSubtasksCount;
use App\Ticket\Properties\ContactId;
use App\Ticket\Properties\CreatedAt;
use App\Ticket\Properties\Customer;
use App\Ticket\Properties\CustomerId;
use App\Ticket\Properties\Description;
use App\Ticket\Properties\DueOn;
use App\Ticket\Properties\EstimateMinutes;
use App\Ticket\Properties\Id;
use App\Ticket\Properties\Labels;
use App\Ticket\Properties\LocationId;
use App\Ticket\Properties\Number;
use App\Ticket\Properties\ParentId;
use App\Ticket\Properties\Position;
use App\Ticket\Properties\PriorityId;
use App\Ticket\Properties\Project;
use App\Ticket\Properties\ProjectId;
use App\Ticket\Properties\StartsOn;
use App\Ticket\Properties\StatusId;
use App\Ticket\Properties\SubtasksCount;
use App\Ticket\Properties\TeamId;
use App\Ticket\Properties\Title;
use App\Ticket\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<TicketEloquentModel>
 */
final class Ticket extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public Assignee $assignee,
        public AssigneeId $assigneeId,
        public ClosedAt $closedAt,
        public ClosedSubtasksCount $closedSubtasksCount,
        public ContactId $contactId,
        public CreatedAt $createdAt,
        public Customer $customer,
        public CustomerId $customerId,
        public Description $description,
        public DueOn $dueOn,
        public EstimateMinutes $estimateMinutes,
        public Id $id,
        public Labels $labels,
        public LocationId $locationId,
        public Number $number,
        public ParentId $parentId,
        public Position $position,
        public PriorityId $priorityId,
        public Project $project,
        public ProjectId $projectId,
        public StartsOn $startsOn,
        public StatusId $statusId,
        public SubtasksCount $subtasksCount,
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

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): TicketCollection
    {
        if ($items instanceof TicketCollection) {
            return $items;
        }

        return TicketCollection::make(parent::collect($items, $method)->all());
    }

    public function isArchived(): bool
    {
        return $this->getArchivedAt()->isNotNull();
    }

    /**
     * The fields the ticket pages show.
     *
     * @return array{id: string, number: int, title: string, description: ?string, status_id: ?string, priority_id: ?string, customer_id: ?string, project_id: ?string, parent_id: ?string, contact_id: ?string, location_id: ?string, assignee_id: ?string, starts_on: ?string, due_on: ?string, estimate_minutes: ?int, position: int, closed_at: ?string, archived_at: ?string, created_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'title' => $this->getTitle()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'status_id' => $this->getStatusId()->toNullableString(),
            'priority_id' => $this->getPriorityId()->toNullableString(),
            'customer_id' => $this->getCustomerId()->toNullableString(),
            'project_id' => $this->getProjectId()->toNullableString(),
            'parent_id' => $this->getParentId()->toNullableString(),
            'contact_id' => $this->getContactId()->toNullableString(),
            'location_id' => $this->getLocationId()->toNullableString(),
            'assignee_id' => $this->getAssigneeId()->toNullableString(),
            'starts_on' => $this->getStartsOn()->toNullableCarbon()?->toDateString(),
            'due_on' => $this->getDueOn()->toNullableCarbon()?->toDateString(),
            'estimate_minutes' => $this->getEstimateMinutes()->toNullableInteger(),
            'position' => $this->getPosition()->toInteger(),
            'closed_at' => $this->getClosedAt()->toNullableISOString(),
            'archived_at' => $this->getArchivedAt()->toNullableISOString(),
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

    public function getPriorityId(): PriorityId
    {
        return $this->priorityId;
    }

    public function setPriorityId(mixed $priorityId): self
    {
        $this->priorityId = new PriorityId($priorityId instanceof AbstractValue ? $priorityId->toValue() : $priorityId);

        return $this;
    }

    public function getStatusId(): StatusId
    {
        return $this->statusId;
    }

    public function setStatusId(mixed $statusId): self
    {
        $this->statusId = new StatusId($statusId instanceof AbstractValue ? $statusId->toValue() : $statusId);

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

    public function getArchivedAt(): ArchivedAt
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(mixed $archivedAt): self
    {
        $this->archivedAt = new ArchivedAt($archivedAt instanceof AbstractValue ? $archivedAt->toValue() : $archivedAt);

        return $this;
    }

    public function getDueOn(): DueOn
    {
        return $this->dueOn;
    }

    public function setDueOn(mixed $dueOn): self
    {
        $this->dueOn = new DueOn($dueOn instanceof AbstractValue ? $dueOn->toValue() : $dueOn);

        return $this;
    }

    public function getEstimateMinutes(): EstimateMinutes
    {
        return $this->estimateMinutes;
    }

    public function setEstimateMinutes(mixed $estimateMinutes): self
    {
        $this->estimateMinutes = new EstimateMinutes($estimateMinutes instanceof AbstractValue ? $estimateMinutes->toValue() : $estimateMinutes);

        return $this;
    }

    public function getParentId(): ParentId
    {
        return $this->parentId;
    }

    public function setParentId(mixed $parentId): self
    {
        $this->parentId = new ParentId($parentId instanceof AbstractValue ? $parentId->toValue() : $parentId);

        return $this;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function setPosition(mixed $position): self
    {
        $this->position = new Position($position instanceof AbstractValue ? $position->toValue() : $position);

        return $this;
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function setProjectId(mixed $projectId): self
    {
        $this->projectId = new ProjectId($projectId instanceof AbstractValue ? $projectId->toValue() : $projectId);

        return $this;
    }

    public function getStartsOn(): StartsOn
    {
        return $this->startsOn;
    }

    public function setStartsOn(mixed $startsOn): self
    {
        $this->startsOn = new StartsOn($startsOn instanceof AbstractValue ? $startsOn->toValue() : $startsOn);

        return $this;
    }

    public function getSubtasksCount(): SubtasksCount
    {
        return $this->subtasksCount;
    }

    public function getClosedSubtasksCount(): ClosedSubtasksCount
    {
        return $this->closedSubtasksCount;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getAssignee(): Assignee
    {
        return $this->assignee;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }
}
