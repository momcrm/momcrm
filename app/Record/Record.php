<?php

declare(strict_types=1);

namespace App\Record;

use App\CustomField\Properties\CustomFieldValues;
use App\Record\Factories\RecordFactory;
use App\Record\Properties\CreatedAt;
use App\Record\Properties\CreatedById;
use App\Record\Properties\Id;
use App\Record\Properties\Name;
use App\Record\Properties\Number;
use App\Record\Properties\ObjectId;
use App\Record\Properties\TeamId;
use App\Record\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One record of a custom object, such as one vehicle among a team's
 * Vehicles. Beyond its name, what it holds are its object's custom fields.
 *
 * @extends AbstractData<RecordEloquentModel>
 */
final class Record extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CreatedById $createdById,
        public CustomFieldValues $customFields,
        public Id $id,
        public Name $name,
        public Number $number,
        public ObjectId $objectId,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): RecordFactory
    {
        return RecordEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): RecordCollection
    {
        if ($items instanceof RecordCollection) {
            return $items;
        }

        return RecordCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the record pages show.
     *
     * @return array{id: string, object_id: string, number: int, name: string, custom_fields: array<string, mixed>, created_at: ?string, updated_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'object_id' => $this->getObjectId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'name' => $this->getName()->toString(),
            'custom_fields' => $this->getCustomFields()->toValues(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
            'updated_at' => $this->getUpdatedAt()->toNullableISOString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCreatedById(): CreatedById
    {
        return $this->createdById;
    }

    public function setCreatedById(mixed $createdById): self
    {
        $this->createdById = new CreatedById($createdById instanceof AbstractValue ? $createdById->toValue() : $createdById);

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

    public function getNumber(): Number
    {
        return $this->number;
    }

    public function setNumber(mixed $number): self
    {
        $this->number = new Number($number instanceof AbstractValue ? $number->toValue() : $number);

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
