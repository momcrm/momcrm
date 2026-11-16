<?php

declare(strict_types=1);

namespace App\Folder;

use App\Folder\Factories\FolderFactory;
use App\Folder\Properties\CreatedAt;
use App\Folder\Properties\CustomerId;
use App\Folder\Properties\Id;
use App\Folder\Properties\Name;
use App\Folder\Properties\ParentId;
use App\Folder\Properties\TeamId;
use App\Folder\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A folder that sorts a customer's files. Folders nest under one another.
 *
 * @extends AbstractData<FolderEloquentModel>
 */
final class Folder extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Id $id,
        public Name $name,
        public ParentId $parentId,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): FolderFactory
    {
        return FolderEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): FolderCollection
    {
        if ($items instanceof FolderCollection) {
            return $items;
        }

        return FolderCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * @return array{id: string, parent_id: ?string, name: string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'parent_id' => $this->getParentId()->toNullableString(),
            'name' => $this->getName()->toString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function setCustomerId(mixed $customerId): self
    {
        $this->customerId = new CustomerId($customerId instanceof AbstractValue ? $customerId->toValue() : $customerId);

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

    public function getParentId(): ParentId
    {
        return $this->parentId;
    }

    public function setParentId(mixed $parentId): self
    {
        $this->parentId = new ParentId($parentId instanceof AbstractValue ? $parentId->toValue() : $parentId);

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
