<?php

declare(strict_types=1);

namespace App\ArticleCategory\Actions;

use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleCategory\Properties\Id;

final readonly class CreateArticleCategory
{
    public function __construct(
        private ArticleCategory $category,
    ) {}

    public function __invoke(): ArticleCategory
    {
        if ($this->category->getId()->isNull()) {
            $this->category->setId(Id::generate());
        }

        $model = ArticleCategoryEloquentModel::query()->create($this->category->forDatabaseCreate());

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
