<?php

declare(strict_types=1);

namespace App\WorkSchedule;

use App\WorkSchedule\Factories\WorkScheduleFactory;
use App\WorkSchedule\Properties\CreatedAt;
use App\WorkSchedule\Properties\Days;
use App\WorkSchedule\Properties\GraceMinutes;
use App\WorkSchedule\Properties\Id;
use App\WorkSchedule\Properties\IsDefault;
use App\WorkSchedule\Properties\Name;
use App\WorkSchedule\Properties\TeamId;
use App\WorkSchedule\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * The hours a team expects someone to work each weekday, such as "Mon–Fri
 * 9–5", with the grace allowed before they count as late.
 *
 * @extends AbstractData<WorkScheduleEloquentModel>
 */
final class WorkSchedule extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Days $days,
        public GraceMinutes $graceMinutes,
        public Id $id,
        public IsDefault $isDefault,
        public Name $name,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): WorkScheduleFactory
    {
        return WorkScheduleEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): WorkScheduleCollection
    {
        if ($items instanceof WorkScheduleCollection) {
            return $items;
        }

        return WorkScheduleCollection::make(parent::collect($items, $method)->all());
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getDays(): Days
    {
        return $this->days;
    }

    public function setDays(mixed $days): self
    {
        $this->days = new Days($days instanceof AbstractValue ? $days->toValue() : $days);

        return $this;
    }

    public function getGraceMinutes(): GraceMinutes
    {
        return $this->graceMinutes;
    }

    public function setGraceMinutes(mixed $graceMinutes): self
    {
        $this->graceMinutes = new GraceMinutes($graceMinutes instanceof AbstractValue ? $graceMinutes->toValue() : $graceMinutes);

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

    public function getIsDefault(): IsDefault
    {
        return $this->isDefault;
    }

    public function setIsDefault(mixed $isDefault): self
    {
        $this->isDefault = new IsDefault($isDefault instanceof AbstractValue ? $isDefault->toValue() : $isDefault);

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

    /**
     * The schedule as the settings page and the calculator's callers show it.
     *
     * @return array{id: string, name: string, is_default: bool, grace_minutes: int, days: array<string, array{starts_at: string, ends_at: string, break_minutes: int}|null>}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'is_default' => $this->getIsDefault()->toBoolean(),
            'grace_minutes' => $this->getGraceMinutes()->toInteger(),
            'days' => $this->getDays()->toDays(),
        ];
    }
}
