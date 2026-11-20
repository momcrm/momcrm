<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Factories\ArticleFactory;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\Author;
use App\Article\Properties\AuthorId;
use App\Article\Properties\Body;
use App\Article\Properties\BodyText;
use App\Article\Properties\Classification;
use App\Article\Properties\CreatedAt;
use App\Article\Properties\Id;
use App\Article\Properties\PublishedAt;
use App\Article\Properties\Status;
use App\Article\Properties\Summary;
use App\Article\Properties\TeamId;
use App\Article\Properties\Title;
use App\Article\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A page of the team's knowledge base: how to do something, or what to know
 * about a system. Its body is rich text (sanitised HTML), and who may read it
 * depends on its classification.
 *
 * @extends AbstractData<ArticleEloquentModel>
 */
final class Article extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public Author $author,
        public AuthorId $authorId,
        public Body $body,
        public BodyText $bodyText,
        public Classification $classification,
        public CreatedAt $createdAt,
        public Id $id,
        public PublishedAt $publishedAt,
        public Status $status,
        public Summary $summary,
        public TeamId $teamId,
        public Title $title,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ArticleFactory
    {
        return ArticleEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ArticleCollection
    {
        if ($items instanceof ArticleCollection) {
            return $items;
        }

        return ArticleCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the articles list shows.
     *
     * @return array{id: string, title: string, summary: ?string, status: string, classification: string, author_name: ?string, published_at: ?string, updated_at: ?string, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'title' => $this->getTitle()->toString(),
            'summary' => $this->getSummary()->toNullableString(),
            'status' => $this->getStatus()->toEnum()->value,
            'classification' => $this->getClassification()->toEnum()->value,
            'author_name' => $this->getAuthor()->toNullableData()?->getName()->toString(),
            'published_at' => $this->getPublishedAt()->toNullableISOString(),
            'updated_at' => $this->getUpdatedAt()->toNullableISOString(),
            'is_archived' => $this->isArchived(),
        ];
    }

    /**
     * Everything the article's page and editor show.
     *
     * @return array{id: string, title: string, summary: ?string, status: string, classification: string, author_name: ?string, published_at: ?string, updated_at: ?string, is_archived: bool, body: string, created_at: ?string}
     */
    public function toDetail(): array
    {
        return [
            ...$this->toSummary(),
            'body' => $this->getBody()->toString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    public function isArchived(): bool
    {
        return $this->archivedAt->isNotNull();
    }

    public function isPublished(): bool
    {
        return $this->getStatus()->toEnum() === ArticleStatusEnum::Published;
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

    public function getAuthor(): Author
    {
        return $this->author;
    }

    public function getAuthorId(): AuthorId
    {
        return $this->authorId;
    }

    public function setAuthorId(mixed $authorId): self
    {
        $this->authorId = new AuthorId($authorId instanceof AbstractValue ? $authorId->toValue() : $authorId);

        return $this;
    }

    public function getBody(): Body
    {
        return $this->body;
    }

    public function setBody(mixed $body): self
    {
        $this->body = new Body($body instanceof AbstractValue ? $body->toValue() : $body);

        return $this;
    }

    public function getBodyText(): BodyText
    {
        return $this->bodyText;
    }

    public function setBodyText(mixed $bodyText): self
    {
        $this->bodyText = new BodyText($bodyText instanceof AbstractValue ? $bodyText->toValue() : $bodyText);

        return $this;
    }

    public function getClassification(): Classification
    {
        return $this->classification;
    }

    public function setClassification(mixed $classification): self
    {
        $this->classification = new Classification($classification instanceof AbstractValue ? $classification->toValue() : $classification);

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

    public function getPublishedAt(): PublishedAt
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(mixed $publishedAt): self
    {
        $this->publishedAt = new PublishedAt($publishedAt instanceof AbstractValue ? $publishedAt->toValue() : $publishedAt);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

        return $this;
    }

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    public function setSummary(mixed $summary): self
    {
        $this->summary = new Summary($summary instanceof AbstractValue ? $summary->toValue() : $summary);

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

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(mixed $title): self
    {
        $this->title = new Title($title instanceof AbstractValue ? $title->toValue() : $title);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
