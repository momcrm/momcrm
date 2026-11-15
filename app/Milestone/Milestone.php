<?php

declare(strict_types=1);

namespace App\Milestone;

use App\Milestone\Factories\MilestoneFactory;
use App\Milestone\Properties\CompletedAt;
use App\Milestone\Properties\CreatedAt;
use App\Milestone\Properties\DueOn;
use App\Milestone\Properties\Id;
use App\Milestone\Properties\Name;
use App\Milestone\Properties\ProjectId;
use App\Milestone\Properties\TeamId;
use App\Milestone\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<MilestoneEloquentModel>
 */
final class Milestone extends AbstractData
{
    public function __construct(
        public CompletedAt $completedAt,
        public CreatedAt $createdAt,
        public DueOn $dueOn,
        public Id $id,
        public Name $name,
        public ProjectId $projectId,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): MilestoneFactory
    {
        return MilestoneEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): MilestoneCollection
    {
        if ($items instanceof MilestoneCollection) {
            return $items;
        }

        return MilestoneCollection::make(parent::collect($items, $method)->all());
    }

    public function getCompletedAt(): CompletedAt
    {
        return $this->completedAt;
    }

    public function setCompletedAt(mixed $completedAt): self
    {
        $this->completedAt = new CompletedAt($completedAt instanceof AbstractValue ? $completedAt->toValue() : $completedAt);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

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

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
    }

    public function setProjectId(mixed $projectId): self
    {
        $this->projectId = new ProjectId($projectId instanceof AbstractValue ? $projectId->toValue() : $projectId);

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
