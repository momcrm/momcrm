<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleBody;
use App\Article\ArticleEloquentModel;
use App\ArticleVersion\Actions\CreateArticleVersion;
use Carbon\CarbonImmutable;

/**
 * Saves an article. The body is sanitised and its plain text kept beside it.
 *
 * Until an article is first published it is edited in place. Publishing it
 * makes it version 1.0. From then on, each save that changes its title,
 * summary, body or classification first keeps it as it was (an
 * ArticleVersion) and moves it to the next minor version, or the next major
 * one when asked. Publishing or unpublishing alone changes no version.
 */
final readonly class UpdateArticle
{
    public function __construct(
        private Article $article,
        private ?string $editedById = null,
        private bool $isMajor = false,
    ) {}

    public function __invoke(): Article
    {
        $body = ArticleBody::sanitise($this->article->getBody()->toNullableString());
        $this->article
            ->setBody($body === '' ? null : $body)
            ->setBodyText(ArticleBody::text($body) ?: null);

        $model = ArticleEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->article->getId()->toString());
        $before = Article::fromEloquentModel($model);
        $isChanged = self::isChanged($before, $this->article);

        if ($before->hasBeenPublished() && $isChanged) {
            CreateArticleVersion::fromArticle($before);

            $major = $before->getVersionMajor()->toInteger();
            $this->article
                ->setVersionMajor($this->isMajor ? $major + 1 : $major)
                ->setVersionMinor($this->isMajor ? 0 : $before->getVersionMinor()->toInteger() + 1)
                ->setEditedById($this->editedById);
        } elseif ($this->article->isPublished() && ! $before->hasBeenPublished()) {
            $this->article
                ->setPublishedAt(CarbonImmutable::now())
                ->setVersionMajor(1)
                ->setVersionMinor(0)
                ->setEditedById($this->editedById);
        } else {
            $this->article->setChangeNote($before->getChangeNote());

            if ($isChanged) {
                $this->article->setEditedById($this->editedById);
            }
        }

        $model->update($this->article->forDatabaseUpdate());

        return Article::fromEloquentModel($model->refresh());
    }

    public static function fromArticle(Article $article, ?string $editedById = null, bool $isMajor = false): Article
    {
        return new self($article, $editedById, $isMajor)->handle();
    }

    public function handle(): Article
    {
        return $this->__invoke();
    }

    public function execute(): Article
    {
        return $this->__invoke();
    }

    private static function isChanged(Article $before, Article $after): bool
    {
        return $before->getTitle()->toString() !== $after->getTitle()->toString()
            || $before->getSummary()->toString() !== $after->getSummary()->toString()
            || $before->getBody()->toString() !== $after->getBody()->toString()
            || $before->getClassification()->toEnum() !== $after->getClassification()->toEnum();
    }
}
