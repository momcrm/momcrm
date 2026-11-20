<?php

declare(strict_types=1);

namespace App\Article;

use App\Article\Properties\Classification;
use App\Shared\RequestMemo;
use App\TeamUser\Actions\FindTeamUser;
use App\TeamUser\TeamRoleEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may read an article: everyone in the team for internal ones, owners
 * and admins for confidential ones, owners alone for strictly confidential
 * ones. Every place that shows articles reads through here.
 */
final class ArticleVisibility
{
    /**
     * Someone's role in the team, or null when they aren't in it.
     */
    public static function roleOf(string $teamId, string $userId): ?TeamRoleEnum
    {
        return RequestMemo::remember(
            "article-role.{$teamId}.{$userId}",
            fn (): ?TeamRoleEnum => FindTeamUser::byTeamAndUser($teamId, $userId)->getRole()->toNullableEnum(),
        );
    }

    /**
     * @return list<ArticleClassificationEnum>
     */
    public static function classificationsFor(string $teamId, string $userId): array
    {
        return ArticleClassificationEnum::readableBy(self::roleOf($teamId, $userId));
    }

    /**
     * Narrows a query of articles to the ones this person may read.
     *
     * @param  Builder<ArticleEloquentModel>  $query
     * @return Builder<ArticleEloquentModel>
     */
    public static function apply(Builder $query, string $teamId, string $userId): Builder
    {
        return $query->whereIn(
            Classification::getDatabaseTableColumnName(),
            array_map(fn (ArticleClassificationEnum $classification): string => $classification->value, self::classificationsFor($teamId, $userId)),
        );
    }

    public static function allows(Article $article, string $userId): bool
    {
        $role = self::roleOf($article->getTeamId()->toString(), $userId);

        return $role !== null && $article->getClassification()->toEnum()->isReadableBy($role);
    }
}
