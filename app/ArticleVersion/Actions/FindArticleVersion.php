<?php

declare(strict_types=1);

namespace App\ArticleVersion\Actions;

use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleVisibility;
use App\ArticleVersion\ArticleVersion;
use App\ArticleVersion\ArticleVersionCollection;
use App\ArticleVersion\ArticleVersionEloquentModel;
use App\ArticleVersion\ArticleVersionRelationship;
use App\ArticleVersion\Properties\ArticleId;
use App\ArticleVersion\Properties\Classification;
use App\ArticleVersion\Properties\Id;
use App\ArticleVersion\Properties\VersionMajor;
use App\ArticleVersion\Properties\VersionMinor;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * An article's earlier versions. A version is only shown to people who may
 * read the classification it had, which can be higher than the article's
 * now.
 */
final class FindArticleVersion
{
    /**
     * Newest first, with who saved each.
     */
    public static function listVisibleForArticle(Article $article, string $userId): ArticleVersionCollection
    {
        return ArticleVersion::collect(
            self::visible($article, $userId)
                ->with(ArticleVersionRelationship::EditedBy->value)
                ->orderByDesc(VersionMajor::getDatabaseTableColumnName())
                ->orderByDesc(VersionMinor::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    public static function visibleByIdForArticle(?string $id, Article $article, string $userId): ArticleVersion
    {
        if ($id === null) {
            return ArticleVersion::new();
        }

        return ArticleVersion::fromEloquentModel(
            self::visible($article, $userId)
                ->with(ArticleVersionRelationship::EditedBy->value)
                ->where(Id::getDatabaseTableColumnName(), $id)
                ->first(),
        );
    }

    /**
     * @return Builder<ArticleVersionEloquentModel>
     */
    private static function visible(Article $article, string $userId): Builder
    {
        return ArticleVersionEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(ArticleId::getDatabaseTableColumnName(), $article->getId()->toString())
            ->whereIn(
                Classification::getDatabaseTableColumnName(),
                array_map(
                    fn (ArticleClassificationEnum $classification): string => $classification->value,
                    ArticleVisibility::classificationsFor($article->getTeamId()->toString(), $userId),
                ),
            );
    }
}
