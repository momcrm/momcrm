<?php

declare(strict_types=1);

namespace App\ArticleCategory\Actions;

use App\Article\ArticleEloquentModel;
use App\Article\Properties\CategoryId;
use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleCategory\Properties\ParentId;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a category. What was in it moves up a level: the categories under
 * it and its articles go to its parent, or to no category at the top.
 */
final readonly class DeleteArticleCategory
{
    public function __construct(
        private ArticleCategory $category,
    ) {}

    public function __invoke(): void
    {
        $id = $this->category->getId()->toString();
        $parentId = $this->category->getParentId()->toNullableString();

        DB::transaction(function () use ($id, $parentId): void {
            ArticleCategoryEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(ParentId::getDatabaseTableColumnName(), $id)
                ->update([ParentId::getDatabaseTableColumnName() => $parentId]);

            ArticleEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(CategoryId::getDatabaseTableColumnName(), $id)
                ->update([CategoryId::getDatabaseTableColumnName() => $parentId]);

            ArticleCategoryEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($id)
                ?->delete();
        });
    }

    public static function fromArticleCategory(ArticleCategory $category): void
    {
        new self($category)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
