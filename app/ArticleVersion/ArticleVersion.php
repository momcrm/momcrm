<?php

declare(strict_types=1);

namespace App\ArticleVersion;

use App\ArticleVersion\Factories\ArticleVersionFactory;
use App\ArticleVersion\Properties\ArticleId;
use App\ArticleVersion\Properties\Body;
use App\ArticleVersion\Properties\BodyText;
use App\ArticleVersion\Properties\ChangeNote;
use App\ArticleVersion\Properties\Classification;
use App\ArticleVersion\Properties\CreatedAt;
use App\ArticleVersion\Properties\EditedBy;
use App\ArticleVersion\Properties\EditedById;
use App\ArticleVersion\Properties\Id;
use App\ArticleVersion\Properties\SavedAt;
use App\ArticleVersion\Properties\Summary;
use App\ArticleVersion\Properties\TeamId;
use App\ArticleVersion\Properties\Title;
use App\ArticleVersion\Properties\UpdatedAt;
use App\ArticleVersion\Properties\VersionMajor;
use App\ArticleVersion\Properties\VersionMinor;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * An earlier version of a published article, kept when it was changed: its
 * title, summary, body and classification as they were, and the note its
 * editor left.
 *
 * @extends AbstractData<ArticleVersionEloquentModel>
 */
final class ArticleVersion extends AbstractData
{
    public function __construct(
        public ArticleId $articleId,
        public Body $body,
        public BodyText $bodyText,
        public ChangeNote $changeNote,
        public Classification $classification,
        public CreatedAt $createdAt,
        public EditedBy $editedBy,
        public EditedById $editedById,
        public Id $id,
        public SavedAt $savedAt,
        public Summary $summary,
        public TeamId $teamId,
        public Title $title,
        public UpdatedAt $updatedAt,
        public VersionMajor $versionMajor,
        public VersionMinor $versionMinor,
    ) {}

    public static function getFactory(): ArticleVersionFactory
    {
        return ArticleVersionEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ArticleVersionCollection
    {
        if ($items instanceof ArticleVersionCollection) {
            return $items;
        }

        return ArticleVersionCollection::make(parent::collect($items, $method)->all());
    }

    public function versionLabel(): string
    {
        return $this->getVersionMajor()->toInteger().'.'.$this->getVersionMinor()->toInteger();
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

    public function getChangeNote(): ChangeNote
    {
        return $this->changeNote;
    }

    public function setChangeNote(mixed $changeNote): self
    {
        $this->changeNote = new ChangeNote($changeNote instanceof AbstractValue ? $changeNote->toValue() : $changeNote);

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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getSavedAt(): SavedAt
    {
        return $this->savedAt;
    }

    public function setSavedAt(mixed $savedAt): self
    {
        $this->savedAt = new SavedAt($savedAt instanceof AbstractValue ? $savedAt->toValue() : $savedAt);

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
}
