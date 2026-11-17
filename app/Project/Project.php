<?php

declare(strict_types=1);

namespace App\Project;

use App\CustomField\Properties\CustomFieldValues;
use App\Project\Factories\ProjectFactory;
use App\Project\Properties\ArchivedAt;
use App\Project\Properties\ClosedTicketsCount;
use App\Project\Properties\CreatedAt;
use App\Project\Properties\Customer;
use App\Project\Properties\CustomerId;
use App\Project\Properties\Description;
use App\Project\Properties\DueOn;
use App\Project\Properties\Group;
use App\Project\Properties\GroupId;
use App\Project\Properties\Hue;
use App\Project\Properties\Icon;
use App\Project\Properties\Id;
use App\Project\Properties\IsSample;
use App\Project\Properties\Labels;
use App\Project\Properties\Lead;
use App\Project\Properties\LeadId;
use App\Project\Properties\Members;
use App\Project\Properties\Name;
use App\Project\Properties\Number;
use App\Project\Properties\OverdueTicketsCount;
use App\Project\Properties\StartsOn;
use App\Project\Properties\Status;
use App\Project\Properties\TeamId;
use App\Project\Properties\TicketsCount;
use App\Project\Properties\UpdatedAt;
use App\Project\Properties\Visibility;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<ProjectEloquentModel>
 */
final class Project extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public ClosedTicketsCount $closedTicketsCount,
        public Hue $hue,
        public CreatedAt $createdAt,
        public CustomFieldValues $customFields,
        public Customer $customer,
        public CustomerId $customerId,
        public Description $description,
        public DueOn $dueOn,
        public Group $group,
        public GroupId $groupId,
        public Icon $icon,
        public Id $id,
        public Labels $labels,
        public IsSample $isSample,
        public Lead $lead,
        public LeadId $leadId,
        public Members $members,
        public Name $name,
        public Number $number,
        public OverdueTicketsCount $overdueTicketsCount,
        public StartsOn $startsOn,
        public Status $status,
        public TeamId $teamId,
        public TicketsCount $ticketsCount,
        public UpdatedAt $updatedAt,
        public Visibility $visibility,
    ) {}

    public static function getFactory(): ProjectFactory
    {
        return ProjectEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ProjectCollection
    {
        if ($items instanceof ProjectCollection) {
            return $items;
        }

        return ProjectCollection::make(parent::collect($items, $method)->all());
    }

    public function isArchived(): bool
    {
        return $this->getArchivedAt()->isNotNull();
    }

    /**
     * The fields project pages and pickers show.
     *
     * @return array{id: string, number: int, name: string, description: ?string, icon: ?string, hue: ?int, status: string, visibility: string, customer_id: ?string, lead_id: ?string, group_id: ?string, starts_on: ?string, due_on: ?string, archived_at: ?string, custom_fields: array<string, mixed>}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'name' => $this->getName()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'icon' => $this->getIcon()->toNullableString(),
            'hue' => $this->getHue()->toNullableInteger(),
            'status' => $this->getStatus()->toString(),
            'visibility' => $this->getVisibility()->toString(),
            'customer_id' => $this->getCustomerId()->toNullableString(),
            'lead_id' => $this->getLeadId()->toNullableString(),
            'group_id' => $this->getGroupId()->toNullableString(),
            'starts_on' => $this->getStartsOn()->toNullableCarbon()?->toDateString(),
            'due_on' => $this->getDueOn()->toNullableCarbon()?->toDateString(),
            'archived_at' => $this->getArchivedAt()->toNullableISOString(),
            'custom_fields' => $this->getCustomFields()->toValues(),
        ];
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

    public function getHue(): Hue
    {
        return $this->hue;
    }

    public function setHue(mixed $hue): self
    {
        $this->hue = new Hue($hue instanceof AbstractValue ? $hue->toValue() : $hue);

        return $this;
    }

    public function getCustomFields(): CustomFieldValues
    {
        return $this->customFields;
    }

    public function setCustomFields(mixed $customFields): self
    {
        $this->customFields = new CustomFieldValues($customFields instanceof AbstractValue ? $customFields->toValue() : $customFields);

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

    public function getDueOn(): DueOn
    {
        return $this->dueOn;
    }

    public function setDueOn(mixed $dueOn): self
    {
        $this->dueOn = new DueOn($dueOn instanceof AbstractValue ? $dueOn->toValue() : $dueOn);

        return $this;
    }

    public function getGroupId(): GroupId
    {
        return $this->groupId;
    }

    public function setGroupId(mixed $groupId): self
    {
        $this->groupId = new GroupId($groupId instanceof AbstractValue ? $groupId->toValue() : $groupId);

        return $this;
    }

    public function getIcon(): Icon
    {
        return $this->icon;
    }

    public function setIcon(mixed $icon): self
    {
        $this->icon = new Icon($icon instanceof AbstractValue ? $icon->toValue() : $icon);

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

    public function getIsSample(): IsSample
    {
        return $this->isSample;
    }

    public function setIsSample(mixed $isSample): self
    {
        $this->isSample = new IsSample($isSample instanceof AbstractValue ? $isSample->toValue() : $isSample);

        return $this;
    }

    public function getLeadId(): LeadId
    {
        return $this->leadId;
    }

    public function setLeadId(mixed $leadId): self
    {
        $this->leadId = new LeadId($leadId instanceof AbstractValue ? $leadId->toValue() : $leadId);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

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

    public function getStartsOn(): StartsOn
    {
        return $this->startsOn;
    }

    public function setStartsOn(mixed $startsOn): self
    {
        $this->startsOn = new StartsOn($startsOn instanceof AbstractValue ? $startsOn->toValue() : $startsOn);

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

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
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

    public function getTicketsCount(): TicketsCount
    {
        return $this->ticketsCount;
    }

    public function getClosedTicketsCount(): ClosedTicketsCount
    {
        return $this->closedTicketsCount;
    }

    public function getOverdueTicketsCount(): OverdueTicketsCount
    {
        return $this->overdueTicketsCount;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getMembers(): Members
    {
        return $this->members;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }
}
