<?php

declare(strict_types=1);

namespace App\ArticleLink;

use App\ArticleLink\Factories\ArticleLinkFactory;
use App\ArticleLink\Properties\ArticleId;
use App\ArticleLink\Properties\CreatedAt;
use App\ArticleLink\Properties\CreatedById;
use App\ArticleLink\Properties\Id;
use App\ArticleLink\Properties\LinkableId;
use App\ArticleLink\Properties\LinkableType;
use App\ArticleLink\Properties\TeamId;
use App\ArticleLink\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * An article attached to a ticket, asset or service, so it shows on both.
 *
 * @extends AbstractData<ArticleLinkEloquentModel>
 */
final class ArticleLink extends AbstractData
{
    public function __construct(
        public ArticleId $articleId,
        public CreatedAt $createdAt,
        public CreatedById $createdById,
        public Id $id,
        public LinkableId $linkableId,
        public LinkableType $linkableType,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ArticleLinkFactory
    {
        return ArticleLinkEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ArticleLinkCollection
    {
        if ($items instanceof ArticleLinkCollection) {
            return $items;
        }

        return ArticleLinkCollection::make(parent::collect($items, $method)->all());
    }

    public function getArticleId(): ArticleId
    {
        return $this->articleId;
    }

    public function setArticleId(mixed $articleId): self
    {
        $this->articleId = new ArticleId($articleId instanceof AbstractValue ? $articleId->toValue() : $articleId);

        return $this;
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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getLinkableId(): LinkableId
    {
        return $this->linkableId;
    }

    public function setLinkableId(mixed $linkableId): self
    {
        $this->linkableId = new LinkableId($linkableId instanceof AbstractValue ? $linkableId->toValue() : $linkableId);

        return $this;
    }

    public function getLinkableType(): LinkableType
    {
        return $this->linkableType;
    }

    public function setLinkableType(mixed $linkableType): self
    {
        $this->linkableType = new LinkableType($linkableType instanceof AbstractValue ? $linkableType->toValue() : $linkableType);

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
