<?php

declare(strict_types=1);

namespace App\ShiftBreak;

use App\ShiftBreak\Factories\ShiftBreakFactory;
use App\ShiftBreak\Properties\CreatedAt;
use App\ShiftBreak\Properties\EndedAt;
use App\ShiftBreak\Properties\Id;
use App\ShiftBreak\Properties\ShiftId;
use App\ShiftBreak\Properties\StartedAt;
use App\ShiftBreak\Properties\TeamId;
use App\ShiftBreak\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A break during a shift. An open break has no end yet.
 *
 * @extends AbstractData<ShiftBreakEloquentModel>
 */
final class ShiftBreak extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public EndedAt $endedAt,
        public Id $id,
        public ShiftId $shiftId,
        public StartedAt $startedAt,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ShiftBreakFactory
    {
        return ShiftBreakEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ShiftBreakCollection
    {
        if ($items instanceof ShiftBreakCollection) {
            return $items;
        }

        return ShiftBreakCollection::make(parent::collect($items, $method)->all());
    }

    public function isOpen(): bool
    {
        return $this->getEndedAt()->isNull();
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getEndedAt(): EndedAt
    {
        return $this->endedAt;
    }

    public function setEndedAt(mixed $endedAt): self
    {
        $this->endedAt = new EndedAt($endedAt instanceof AbstractValue ? $endedAt->toValue() : $endedAt);

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

    public function getShiftId(): ShiftId
    {
        return $this->shiftId;
    }

    public function setShiftId(mixed $shiftId): self
    {
        $this->shiftId = new ShiftId($shiftId instanceof AbstractValue ? $shiftId->toValue() : $shiftId);

        return $this;
    }

    public function getStartedAt(): StartedAt
    {
        return $this->startedAt;
    }

    public function setStartedAt(mixed $startedAt): self
    {
        $this->startedAt = new StartedAt($startedAt instanceof AbstractValue ? $startedAt->toValue() : $startedAt);

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
