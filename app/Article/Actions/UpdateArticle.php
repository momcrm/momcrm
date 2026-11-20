<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleBody;
use App\Article\ArticleEloquentModel;
use Carbon\CarbonImmutable;

/**
 * Saves an article. The body is sanitised and its plain text kept beside it,
 * and the first time it is published is remembered.
 */
final readonly class UpdateArticle
{
    public function __construct(
        private Article $article,
    ) {}

    public function __invoke(): Article
    {
        $body = ArticleBody::sanitise($this->article->getBody()->toNullableString());
        $this->article
            ->setBody($body === '' ? null : $body)
            ->setBodyText(ArticleBody::text($body) ?: null);

        if ($this->article->isPublished() && $this->article->getPublishedAt()->isNull()) {
            $this->article->setPublishedAt(CarbonImmutable::now());
        }

        $model = ArticleEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->article->getId()->toString());
        $model->update($this->article->forDatabaseUpdate());

        return Article::fromEloquentModel($model->refresh());
    }

    public static function fromArticle(Article $article): Article
    {
        return new self($article)->handle();
    }

    public function handle(): Article
    {
        return $this->__invoke();
    }

    public function execute(): Article
    {
        return $this->__invoke();
    }
}
