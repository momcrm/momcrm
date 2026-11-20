<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Factories\ArticleFactory;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\Author;
use App\Article\Properties\AuthorId;
use App\Article\Properties\Body;
use App\Article\Properties\BodyText;
use App\Article\Properties\Category;
use App\Article\Properties\CategoryId;
use App\Article\Properties\ChangeNote;
use App\Article\Properties\Classification;
use App\Article\Properties\CreatedAt;
use App\Article\Properties\Customer;
use App\Article\Properties\CustomerId;
use App\Article\Properties\EditedBy;
use App\Article\Properties\EditedById;
use App\Article\Properties\Id;
use App\Article\Properties\Labels;
use App\Article\Properties\LastReviewedOn;
use App\Article\Properties\NextReviewOn;
use App\Article\Properties\PublishedAt;
use App\Article\Properties\Status;
use App\Article\Properties\Summary;
use App\Article\Properties\TeamId;
use App\Article\Properties\Title;
use App\Article\Properties\UpdatedAt;
use App\Article\Properties\VersionMajor;
use App\Article\Properties\VersionMinor;
use Carbon\CarbonImmutable;
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
        public Customer $customer,
        public CustomerId $customerId,
        public Category $category,
        public CategoryId $categoryId,
        public Labels $labels,
        public LastReviewedOn $lastReviewedOn,
        public NextReviewOn $nextReviewOn,
        public ChangeNote $changeNote,
        public EditedBy $editedBy,
        public EditedById $editedById,
        public VersionMajor $versionMajor,
        public VersionMinor $versionMinor,
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
     * @return array{id: string, title: string, summary: ?string, status: string, classification: string, version: ?string, category_id: ?string, customer_id: ?string, customer_name: ?string, labels: list<array{id: string, name: string, hue: ?int}>, next_review_on: ?string, last_reviewed_on: ?string, is_review_due: bool, author_name: ?string, published_at: ?string, updated_at: ?string, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'title' => $this->getTitle()->toString(),
            'summary' => $this->getSummary()->toNullableString(),
            'status' => $this->getStatus()->toEnum()->value,
            'classification' => $this->getClassification()->toEnum()->value,
            'version' => $this->versionLabel(),
            'category_id' => $this->getCategoryId()->toNullableString(),
            'customer_id' => $this->getCustomerId()->toNullableString(),
            'customer_name' => $this->getCustomer()->toNullableData()?->getName()->toString(),
            'labels' => $this->getLabels()->toDataCollection()->toTags(),
            'next_review_on' => $this->getNextReviewOn()->toNullableCarbon()?->toDateString(),
            'last_reviewed_on' => $this->getLastReviewedOn()->toNullableCarbon()?->toDateString(),
            'is_review_due' => $this->isReviewDue(),
            'author_name' => $this->getAuthor()->toNullableData()?->getName()->toString(),
            'published_at' => $this->getPublishedAt()->toNullableISOString(),
            'updated_at' => $this->getUpdatedAt()->toNullableISOString(),
            'is_archived' => $this->isArchived(),
        ];
    }

    /**
     * Everything the article's page and editor show.
     *
     * @return array{id: string, title: string, summary: ?string, status: string, classification: string, version: ?string, category_id: ?string, customer_id: ?string, customer_name: ?string, labels: list<array{id: string, name: string, hue: ?int}>, next_review_on: ?string, last_reviewed_on: ?string, is_review_due: bool, author_name: ?string, published_at: ?string, updated_at: ?string, is_archived: bool, body: string, change_note: ?string, created_at: ?string}
     */
    public function toDetail(): array
    {
        return [
            ...$this->toSummary(),
            'body' => $this->getBody()->toString(),
            'change_note' => $this->getChangeNote()->toNullableString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    /**
     * Its version, such as "1.2", or null until it is first published.
     */
    public function versionLabel(): ?string
    {
        $major = $this->getVersionMajor()->toInteger();

        return $major === 0 ? null : $major.'.'.$this->getVersionMinor()->toInteger();
    }

    /**
     * Whether it has been published, even if it has gone back to a draft
     * since: from then on, each change is kept as a version.
     */
    public function hasBeenPublished(): bool
    {
        return $this->getPublishedAt()->isNotNull();
    }

    /**
     * Whether its review day has come, for an article still in use.
     */
    public function isReviewDue(): bool
    {
        $nextReviewOn = $this->getNextReviewOn()->toNullableCarbon();

        return $nextReviewOn !== null && ! $this->isArchived() && $nextReviewOn->startOfDay()->lte(CarbonImmutable::today());
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

    public function getChangeNote(): ChangeNote
    {
        return $this->changeNote;
    }

    public function setChangeNote(mixed $changeNote): self
    {
        $this->changeNote = new ChangeNote($changeNote instanceof AbstractValue ? $changeNote->toValue() : $changeNote);

        return $this;
    }

    public function getEditedBy(): EditedBy
    {
        return $this->editedBy;
    }

    public function getEditedById(): EditedById
    {
        return $this->editedById;
    }

    public function setEditedById(mixed $editedById): self
    {
        $this->editedById = new EditedById($editedById instanceof AbstractValue ? $editedById->toValue() : $editedById);

        return $this;
    }

    public function getVersionMajor(): VersionMajor
    {
        return $this->versionMajor;
    }

    public function setVersionMajor(mixed $versionMajor): self
    {
        $this->versionMajor = new VersionMajor($versionMajor instanceof AbstractValue ? $versionMajor->toValue() : $versionMajor);

        return $this;
    }

    public function getVersionMinor(): VersionMinor
    {
        return $this->versionMinor;
    }

    public function setVersionMinor(mixed $versionMinor): self
    {
        $this->versionMinor = new VersionMinor($versionMinor instanceof AbstractValue ? $versionMinor->toValue() : $versionMinor);

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getCategoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function setCategoryId(mixed $categoryId): self
    {
        $this->categoryId = new CategoryId($categoryId instanceof AbstractValue ? $categoryId->toValue() : $categoryId);

        return $this;
    }

    public function getLabels(): Labels
    {
        return $this->labels;
    }

    public function getLastReviewedOn(): LastReviewedOn
    {
        return $this->lastReviewedOn;
    }

    public function setLastReviewedOn(mixed $lastReviewedOn): self
    {
        $this->lastReviewedOn = new LastReviewedOn($lastReviewedOn instanceof AbstractValue ? $lastReviewedOn->toValue() : $lastReviewedOn);

        return $this;
    }

    public function getNextReviewOn(): NextReviewOn
    {
        return $this->nextReviewOn;
    }

    public function setNextReviewOn(mixed $nextReviewOn): self
    {
        $this->nextReviewOn = new NextReviewOn($nextReviewOn instanceof AbstractValue ? $nextReviewOn->toValue() : $nextReviewOn);

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
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
}
