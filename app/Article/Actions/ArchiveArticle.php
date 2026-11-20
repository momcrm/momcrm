<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\Properties\ArchivedAt;
use Carbon\CarbonImmutable;

/**
 * Archives an article, or brings it back. An archived article keeps its
 * status and is only listed under the Archived filter.
 */
final readonly class ArchiveArticle
{
    public function __construct(
        private Article $article,
        private bool $archive = true,
    ) {}

    public function __invoke(): Article
    {
        $model = ArticleEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->article->getId()->toString());
        $model->update([ArchivedAt::getDatabaseTableColumnName() => $this->archive ? CarbonImmutable::now() : null]);

        return Article::fromEloquentModel($model->refresh());
    }

    public static function archive(Article $article): Article
    {
        return new self($article, archive: true)->handle();
    }

    public static function restore(Article $article): Article
    {
        return new self($article, archive: false)->handle();
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
