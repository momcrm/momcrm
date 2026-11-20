<?php

declare(strict_types=1);

namespace App\ArticleCategory\Actions;

use App\ArticleCategory\ArticleCategory;
use App\ArticleCategory\ArticleCategoryCollection;
use App\ArticleCategory\ArticleCategoryEloquentModel;
use App\ArticleCategory\Properties\Name;
use App\ArticleCategory\Properties\TeamId;
use App\Team\Scopes\CurrentTeamScope;

final class FindArticleCategory
{
    /**
     * A team's categories by name; toSummaries() puts them in tree order.
     */
    public static function listForTeam(string $teamId): ArticleCategoryCollection
    {
        return ArticleCategory::collect(
            ArticleCategoryEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    public static function byIdForTeam(?string $id, ?string $teamId): ArticleCategory
    {
        if ($id === null || $teamId === null) {
            return ArticleCategory::new();
        }

        return ArticleCategory::fromEloquentModel(
            ArticleCategoryEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->find($id),
        );
    }
}
