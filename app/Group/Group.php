<?php

declare(strict_types=1);

namespace App\Group;

use App\Group\Factories\GroupFactory;
use App\Group\Properties\Color;
use App\Group\Properties\CreatedAt;
use App\Group\Properties\Description;
use App\Group\Properties\Icon;
use App\Group\Properties\Id;
use App\Group\Properties\Name;
use App\Group\Properties\TeamId;
use App\Group\Properties\UpdatedAt;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<GroupEloquentModel>
 */
final class Group extends AbstractData
{
    public function __construct(
        public Color $color,
        public CreatedAt $createdAt,
        public Description $description,
        public Icon $icon,
        public Id $id,
        public Name $name,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): GroupFactory
    {
        return GroupEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
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

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

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
