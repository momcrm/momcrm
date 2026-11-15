<?php

declare(strict_types=1);

namespace App\Label;

use App\Label\Factories\LabelFactory;
use App\Label\Properties\Color;
use App\Label\Properties\CreatedAt;
use App\Label\Properties\Id;
use App\Label\Properties\Name;
use App\Label\Properties\TeamId;
use App\Label\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<LabelEloquentModel>
 */
final class Label extends AbstractData
{
    public function __construct(
        public Color $color,
        public CreatedAt $createdAt,
        public Id $id,
        public Name $name,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): LabelFactory
    {
        return LabelEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): LabelCollection
    {
        if ($items instanceof LabelCollection) {
            return $items;
        }

        return LabelCollection::make(parent::collect($items, $method)->all());
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public function setColor(mixed $color): self
    {
        $this->color = new Color($color instanceof AbstractValue ? $color->toValue() : $color);

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
}
