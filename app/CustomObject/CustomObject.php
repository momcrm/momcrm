<?php

declare(strict_types=1);

namespace App\CustomObject;

use App\CustomObject\Factories\CustomObjectFactory;
use App\CustomObject\Properties\ArchivedAt;
use App\CustomObject\Properties\CreatedAt;
use App\CustomObject\Properties\Description;
use App\CustomObject\Properties\Hue;
use App\CustomObject\Properties\Icon;
use App\CustomObject\Properties\Id;
use App\CustomObject\Properties\Name;
use App\CustomObject\Properties\NamePlural;
use App\CustomObject\Properties\Position;
use App\CustomObject\Properties\TeamId;
use App\CustomObject\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A kind of record a team has defined for itself, such as "Vehicles". Its
 * records live in the records table and its fields are custom fields.
 *
 * @extends AbstractData<CustomObjectEloquentModel>
 */
final class CustomObject extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public CreatedAt $createdAt,
        public Description $description,
        public Hue $hue,
        public Icon $icon,
        public Id $id,
        public Name $name,
        public NamePlural $namePlural,
        public Position $position,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): CustomObjectFactory
    {
        return CustomObjectEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): CustomObjectCollection
    {
        if ($items instanceof CustomObjectCollection) {
            return $items;
        }

        return CustomObjectCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the frontend needs to show the object.
     *
     * @return array{id: string, name: string, name_plural: string, description: ?string, icon: ?string, hue: ?int, position: int, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'name_plural' => $this->getNamePlural()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'icon' => $this->getIcon()->toNullableString(),
            'hue' => $this->getHue()->toNullableInteger(),
            'position' => $this->getPosition()->toInteger(),
            'is_archived' => $this->isArchived(),
        ];
    }

    public function isArchived(): bool
    {
        return $this->archivedAt->isNotNull();
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

    public function getHue(): Hue
    {
        return $this->hue;
    }

    public function setHue(mixed $hue): self
    {
        $this->hue = new Hue($hue instanceof AbstractValue ? $hue->toValue() : $hue);

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

    public function getNamePlural(): NamePlural
    {
        return $this->namePlural;
    }

    public function setNamePlural(mixed $namePlural): self
    {
        $this->namePlural = new NamePlural($namePlural instanceof AbstractValue ? $namePlural->toValue() : $namePlural);

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
