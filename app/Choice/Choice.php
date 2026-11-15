<?php

declare(strict_types=1);

namespace App\Choice;

use App\Choice\Factories\ChoiceFactory;
use App\Choice\Properties\ArchivedAt;
use App\Choice\Properties\Category;
use App\Choice\Properties\Color;
use App\Choice\Properties\CreatedAt;
use App\Choice\Properties\Icon;
use App\Choice\Properties\Id;
use App\Choice\Properties\IsDefault;
use App\Choice\Properties\ListName;
use App\Choice\Properties\Name;
use App\Choice\Properties\Position;
use App\Choice\Properties\TeamId;
use App\Choice\Properties\UpdatedAt;
use BackedEnum;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One entry in a team's customisable list, such as the ticket status
 * "Waiting on customer" or the priority "Urgent".
 *
 * @extends AbstractData<ChoiceEloquentModel>
 */
final class Choice extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public Category $category,
        public Color $color,
        public CreatedAt $createdAt,
        public Icon $icon,
        public Id $id,
        public IsDefault $isDefault,
        public ListName $listName,
        public Name $name,
        public Position $position,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ChoiceFactory
    {
        return ChoiceEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ChoiceCollection
    {
        if ($items instanceof ChoiceCollection) {
            return $items;
        }

        return ChoiceCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the frontend needs to show the choice.
     *
     * @return array{id: string, name: string, color: string, icon: ?string, category: ?string, position: int, is_default: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'color' => $this->getColor()->toEnum()->value,
            'icon' => $this->getIcon()->toNullableString(),
            'category' => $this->getCategory()->toNullableString(),
            'position' => $this->getPosition()->toInteger(),
            'is_default' => $this->getIsDefault()->toBoolean(),
        ];
    }

    /**
     * Whether the choice is in this category, such as a closed ticket status.
     */
    public function hasCategory(BackedEnum $category): bool
    {
        return $this->getCategory()->toNullableString() === (string) $category->value;
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

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(mixed $category): self
    {
        $this->category = new Category($category instanceof AbstractValue ? $category->toValue() : $category);

        return $this;
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

    public function getIsDefault(): IsDefault
    {
        return $this->isDefault;
    }

    public function setIsDefault(mixed $isDefault): self
    {
        $this->isDefault = new IsDefault($isDefault instanceof AbstractValue ? $isDefault->toValue() : $isDefault);

        return $this;
    }

    public function getListName(): ListName
    {
        return $this->listName;
    }

    public function setListName(mixed $listName): self
    {
        $this->listName = new ListName($listName instanceof AbstractValue ? $listName->toValue() : $listName);

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
