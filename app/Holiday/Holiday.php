<?php

declare(strict_types=1);

namespace App\Holiday;

use App\Holiday\Factories\HolidayFactory;
use App\Holiday\Properties\CreatedAt;
use App\Holiday\Properties\Date;
use App\Holiday\Properties\Id;
use App\Holiday\Properties\Name;
use App\Holiday\Properties\TeamId;
use App\Holiday\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A day nobody in the team is expected to work, such as a public holiday.
 *
 * @extends AbstractData<HolidayEloquentModel>
 */
final class Holiday extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Date $date,
        public Id $id,
        public Name $name,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): HolidayFactory
    {
        return HolidayEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): HolidayCollection
    {
        if ($items instanceof HolidayCollection) {
            return $items;
        }

        return HolidayCollection::make(parent::collect($items, $method)->all());
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getDate(): Date
    {
        return $this->date;
    }

    public function setDate(mixed $date): self
    {
        $this->date = new Date($date instanceof AbstractValue ? $date->toValue() : $date);

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
     * @return array{id: string, date: string, name: string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'date' => $this->getDate()->toDateString(),
            'name' => $this->getName()->toString(),
        ];
    }
}
