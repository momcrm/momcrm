<?php

declare(strict_types=1);

namespace App\ArticleLink\Actions;

use App\Article\Article;
use App\Article\ArticleEloquentModel;
use App\Article\ArticleVisibility;
use App\Article\Properties\ArchivedAt;
use App\Article\Properties\Title;
use App\ArticleLink\ArticleLink;
use App\ArticleLink\ArticleLinkEloquentModel;
use App\ArticleLink\ArticleLinkTargetEnum;
use App\ArticleLink\Properties\ArticleId;
use App\ArticleLink\Properties\LinkableId;
use App\ArticleLink\Properties\LinkableType;
use App\ArticleLink\Properties\TeamId;
use App\Shared\Table;
use App\Shared\Types;
use App\Team\Scopes\CurrentTeamScope;

/**
 * The articles attached to a record, and the records an article is
 * attached to, each only as far as the person may see them.
 */
final class FindArticleLink
{
    public static function byIdForTeam(?string $id, ?string $teamId): ArticleLink
    {
        if ($id === null || $teamId === null) {
            return ArticleLink::new();
        }

        return ArticleLink::fromEloquentModel(
            ArticleLinkEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->where(TeamId::getDatabaseTableColumnName(), $teamId)
                ->find($id),
        );
    }

    /**
     * The articles attached to a record that the person may read, by title.
     *
     * @return list<array{link_id: string, id: string, title: string, summary: ?string, status: string, classification: string, is_archived: bool}>
     */
    public static function articlesFor(ArticleLinkTargetEnum $target, string $recordId, string $teamId, string $userId): array
    {
        $links = ArticleLinkEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(LinkableType::getDatabaseTableColumnName(), $target->value)
            ->where(LinkableId::getDatabaseTableColumnName(), $recordId)
            ->pluck(ArticleId::getDatabaseTableColumnName(), 'id');

        if ($links->isEmpty()) {
            return [];
        }

        $linkIdsByArticle = $links->flip();
        $articles = ArticleVisibility::apply(
            ArticleEloquentModel::query()->withoutGlobalScope(CurrentTeamScope::class)->where('team_id', $teamId),
            $teamId,
            $userId,
        )
            ->whereKey($links->values()->all())
            ->orderBy(Title::getDatabaseTableColumnNameWithTable(Table::Articles->value))
            ->get();

        return array_values($articles->map(function (ArticleEloquentModel $model) use ($linkIdsByArticle): array {
            $article = Article::fromEloquentModel($model);

            return [
                'link_id' => (string) $linkIdsByArticle[$model->id],
                'id' => $model->id,
                'title' => $article->getTitle()->toString(),
                'summary' => $article->getSummary()->toNullableString(),
                'status' => $article->getStatus()->toEnum()->value,
                'classification' => $article->getClassification()->toEnum()->value,
                'is_archived' => $model->getAttribute(ArchivedAt::getDatabaseTableColumnName()) !== null,
            ];
        })->all());
    }

    /**
     * The records an article is attached to that the person can see, by kind.
     *
     * @return list<array{kind: string, records: list<array{link_id: string, id: string, title: string, url: string}>}>
     */
    public static function recordsFor(Article $article): array
    {
        $teamId = $article->getTeamId()->toString();
        $links = ArticleLinkEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->where(ArticleId::getDatabaseTableColumnName(), $article->getId()->toString())
            ->get();
        $groups = [];

        foreach (ArticleLinkTargetEnum::cases() as $target) {
            $ofKind = $links->where(LinkableType::getDatabaseTableColumnName(), $target->value);
            $described = $target->describe(Types::stringList($ofKind->pluck(LinkableId::getDatabaseTableColumnName())->values()), $teamId);
            $records = [];

            foreach ($ofKind as $link) {
                if (isset($described[$link->linkable_id])) {
                    $records[] = ['link_id' => $link->id, 'id' => $link->linkable_id, ...$described[$link->linkable_id]];
                }
            }

            if ($records !== []) {
                usort($records, fn (array $a, array $b): int => strnatcasecmp($a['title'], $b['title']));
                $groups[] = ['kind' => $target->label(), 'records' => $records];
            }
        }

        return $groups;
    }
}
