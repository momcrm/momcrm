<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomField\Factories\CustomFieldFactory;
use App\CustomField\Properties\ArchivedAt;
use App\CustomField\Properties\CreatedAt;
use App\CustomField\Properties\Description;
use App\CustomField\Properties\Id;
use App\CustomField\Properties\Name;
use App\CustomField\Properties\ObjectId;
use App\CustomField\Properties\Options;
use App\CustomField\Properties\Position;
use App\CustomField\Properties\Relation;
use App\CustomField\Properties\Target;
use App\CustomField\Properties\TeamId;
use App\CustomField\Properties\Type;
use App\CustomField\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A field a team has added to one kind of record, such as a "Serial number"
 * text field on tickets. The values live on the records themselves.
 *
 * @extends AbstractData<CustomFieldEloquentModel>
 */
final class CustomField extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public CreatedAt $createdAt,
        public Description $description,
        public Id $id,
        public Name $name,
        public ObjectId $objectId,
        public Options $options,
        public Position $position,
        public Relation $relation,
        public Target $target,
        public TeamId $teamId,
        public Type $type,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): CustomFieldFactory
    {
        return CustomFieldEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): CustomFieldCollection
    {
        if ($items instanceof CustomFieldCollection) {
            return $items;
        }

        return CustomFieldCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the frontend needs to show the field and its input.
     *
     * @return array{id: string, target: string, object_id: ?string, type: string, relation: ?string, name: string, description: ?string, options: list<array{id: string, name: string, hue: ?int}>, position: int, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'target' => $this->getTarget()->toEnum()->value,
            'object_id' => $this->getObjectId()->toNullableString(),
            'type' => $this->getType()->toEnum()->value,
            'relation' => $this->getRelation()->toNullableEnum()?->value,
            'name' => $this->getName()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'options' => $this->getOptions()->toOptions(),
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

    public function getObjectId(): ObjectId
    {
        return $this->objectId;
    }

    public function setObjectId(mixed $objectId): self
    {
        $this->objectId = new ObjectId($objectId instanceof AbstractValue ? $objectId->toValue() : $objectId);

        return $this;
    }

    public function getOptions(): Options
    {
        return $this->options;
    }

    public function setOptions(mixed $options): self
    {
        $this->options = new Options($options instanceof AbstractValue ? $options->toValue() : $options);

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

    public function getRelation(): Relation
    {
        return $this->relation;
    }

    public function setRelation(mixed $relation): self
    {
        $this->relation = new Relation($relation instanceof AbstractValue ? $relation->toValue() : $relation);

        return $this;
    }

    public function getTarget(): Target
    {
        return $this->target;
    }

    public function setTarget(mixed $target): self
    {
        $this->target = new Target($target instanceof AbstractValue ? $target->toValue() : $target);

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

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(mixed $type): self
    {
        $this->type = new Type($type instanceof AbstractValue ? $type->toValue() : $type);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
