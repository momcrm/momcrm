<?php

declare(strict_types=1);

namespace App\ArticleCategory\Actions;

use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryEloquentModel;

final readonly class UpdateArticleCategory
{
    public function __construct(
        private ArticleCategory $category,
    ) {}

    public function __invoke(): ArticleCategory
    {
        $model = ArticleCategoryEloquentModel::query()->withoutGlobalScopes()->findOrFail($this->category->getId()->toString());
        $model->update($this->category->forDatabaseUpdate());

        return ArticleCategory::fromEloquentModel($model->refresh());
    }

    public static function fromArticleCategory(ArticleCategory $category): ArticleCategory
    {
        return new self($category)->handle();
    }

    public function handle(): ArticleCategory
    {
        return $this->__invoke();
    }

    public function execute(): ArticleCategory
    {
        return $this->__invoke();
    }
}
