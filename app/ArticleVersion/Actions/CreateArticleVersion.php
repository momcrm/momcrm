<?php

declare(strict_types=1);

namespace App\ArticleVersion\Actions;

use App\Article\Article;
use App\ArticleVersion\ArticleVersion;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\ArticleVersion\Properties\Id;

/**
 * Keeps an article as it is now, before it is changed.
 */
final readonly class CreateArticleVersion
{
    public function __construct(
        private Article $article,
    ) {}

    public function __invoke(): ArticleVersion
    {
        $version = ArticleVersion::new()
            ->setId(Id::generate())
            ->setTeamId($this->article->getTeamId())
            ->setArticleId($this->article->getId())
            ->setEditedById($this->article->getEditedById())
            ->setVersionMajor($this->article->getVersionMajor())
            ->setVersionMinor($this->article->getVersionMinor())
            ->setTitle($this->article->getTitle())
            ->setSummary($this->article->getSummary())
            ->setBody($this->article->getBody())
            ->setBodyText($this->article->getBodyText())
            ->setClassification($this->article->getClassification()->toEnum())
            ->setChangeNote($this->article->getChangeNote())
            ->setSavedAt($this->article->getUpdatedAt()->toNullableCarbon());

        $model = ArticleVersionEloquentModel::query()->create($version->forDatabaseCreate());

        return ArticleVersion::fromEloquentModel($model->refresh());
    }

    public static function fromArticle(Article $article): ArticleVersion
    {
        return new self($article)->handle();
    }

    public function handle(): ArticleVersion
    {
        return $this->__invoke();
    }

    public function execute(): ArticleVersion
    {
        return $this->__invoke();
    }
}
