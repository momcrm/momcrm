<?php

declare(strict_types=1);

namespace App\ArticleCategory;

use App\ArticleCategory\Factories\ArticleCategoryFactory;
use App\ArticleCategory\Properties\CreatedAt;
use App\ArticleCategory\Properties\Id;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\ParentId;
use App\ArticleCategory\Properties\TeamId;
use App\ArticleCategory\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A place to file articles, such as "Networking" or "Networking › Routers".
 * Categories nest, so filtering by one includes the ones under it.
 *
 * @extends AbstractData<ArticleCategoryEloquentModel>
 */
final class ArticleCategory extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Id $id,
        public Name $name,
        public ParentId $parentId,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ArticleCategoryFactory
    {
        return ArticleCategoryEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ArticleCategoryCollection
    {
        if ($items instanceof ArticleCategoryCollection) {
            return $items;
        }

        return ArticleCategoryCollection::make(parent::collect($items, $method)->all());
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
