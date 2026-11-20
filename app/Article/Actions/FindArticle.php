<?php

declare(strict_types=1);

namespace App\Article\Actions;

use App\Article\Article;
use App\Article\ArticleCollection;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleRelationship;
use App\Article\ArticleStatusEnum;
use App\Article\ArticleVisibility;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\BodyText;
use App\Article\Properties\Classification;
use App\Article\Properties\Id;
use App\Article\Properties\Status;
use App\Article\Properties\Summary;
use App\Article\Properties\TeamId;
use App\Article\Properties\Title;
use App\Article\Properties\UpdatedAt;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Reads a team's articles, only ever the ones the person asking may read.
 */
final class FindArticle
{
    /** The list's status filter for archived articles, whatever their status. */
    public const string ARCHIVED = 'archived';

    /** Columns the list can be sorted by. */
    public const array SORTS = ['title', 'updated'];

    /**
     * One article, archived or not; empty when it isn't the team's or the
     * person may not read it.
     */
    public static function visibleByIdForTeam(?string $id, ?string $teamId, string $userId): Article
    {
        if ($id === null || $teamId === null) {
            return Article::new();
        }

        return Article::fromEloquentModel(
            self::visible($teamId, $userId)
                ->with([ArticleRelationship::Author->value, ArticleRelationship::EditedBy->value])
                ->where(Id::getDatabaseTableColumnName(), $id)
                ->first(),
        );
    }

    /**
     * The articles list. Archived articles show only under their own filter.
     *
     * @return LengthAwarePaginator<int, Article>
     */
    public static function paginateVisible(
        string $teamId,
        string $userId,
        ?string $search = null,
        ?string $status = null,
        ?string $classification = null,
        string $sort = 'updated',
        string $direction = 'desc',
        int $perPage = 25,
    ): LengthAwarePaginator {
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        return self::visible($teamId, $userId)
            ->with(ArticleRelationship::Author->value)
            ->when(
                $status === self::ARCHIVED,
                fn (Builder $query) => $query->whereNotNull(ArchivedAt::getDatabaseTableColumnName()),
                fn (Builder $query) => $query->whereNull(ArchivedAt::getDatabaseTableColumnName()),
            )
            ->when(ArticleStatusEnum::tryFrom((string) $status), fn (Builder $query, ArticleStatusEnum $status) => $query->where(Status::getDatabaseTableColumnName(), $status->value))
            ->when($classification, fn (Builder $query, string $classification) => $query->where(Classification::getDatabaseTableColumnName(), $classification))
            ->when($search, fn (Builder $query, string $search) => self::matching($query, $search))
            ->when(
                $sort === 'title',
                fn (Builder $query) => $query->orderBy(Title::getDatabaseTableColumnName(), $direction),
                fn (Builder $query) => $query->orderBy(UpdatedAt::getDatabaseTableColumnName(), $direction),
            )
            ->orderBy(Id::getDatabaseTableColumnName())
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (ArticleEloquentModel $model): Article => Article::fromEloquentModel($model));
    }

    /**
     * Articles that aren't archived whose title, summary or body has the
     * term, most recently changed first; for the ⌘K palette.
     */
    public static function searchVisible(string $teamId, string $userId, string $term, int $limit): ArticleCollection
    {
        return Article::collect(
            self::visible($teamId, $userId)
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->tap(fn (Builder $query) => self::matching($query, $term))
                ->latest(UpdatedAt::getDatabaseTableColumnName())
                ->limit($limit)
                ->get()
                ->all(),
            'fromEloquentModel',
        );
    }

    /**
     * @param  Builder<ArticleEloquentModel>  $query
     * @return Builder<ArticleEloquentModel>
     */
    private static function matching(Builder $query, string $search): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where(Title::getDatabaseTableColumnName(), 'like', "%{$search}%")
            ->orWhere(Summary::getDatabaseTableColumnName(), 'like', "%{$search}%")
            ->orWhere(BodyText::getDatabaseTableColumnName(), 'like', "%{$search}%"));
    }

    /**
     * @return Builder<ArticleEloquentModel>
     */
    private static function visible(string $teamId, string $userId): Builder
    {
        return ArticleVisibility::apply(
            ArticleEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId),
            $teamId,
            $userId,
        );
    }
}
