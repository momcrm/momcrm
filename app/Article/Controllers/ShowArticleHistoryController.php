<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\Exceptions\CouldNotFindArticle;
use App\ArticleVersion\Actions\FindArticleVersion;
use App\ArticleVersion\ArticleVersion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An article's versions, newest first, with one of them shown in full and
 * compared with the version before it.
 */
final class ShowArticleHistoryController
{
    /**
     * @throws CouldNotFindArticle
     */
    public function __invoke(Request $request, string $article): Response
    {
        $userId = (string) $request->user()?->id;
        $found = FindArticle::visibleByIdForTeam($article, $request->user()?->current_team_id, $userId);

        if ($found->isNull()) {
            throw CouldNotFindArticle::notFound();
        }

        $versions = [
            ...($found->hasBeenPublished() ? [self::current($found)] : []),
            ...FindArticleVersion::listVisibleForArticle($found, $userId)->map(self::earlier(...))->values()->all(),
        ];
        $selectedIndex = 0;

        foreach ($versions as $index => $version) {
            if ($version['id'] === $request->query('version')) {
                $selectedIndex = $index;
            }
        }

        return Inertia::render('articles/history', [
            'article' => $found->toSummary(),
            'versions' => array_map(fn (array $version): array => array_diff_key($version, ['body' => true, 'body_text' => true]), $versions),
            'selected' => $versions[$selectedIndex] ?? null,
            'previous' => isset($versions[$selectedIndex + 1])
                ? ['label' => $versions[$selectedIndex + 1]['label'], 'title' => $versions[$selectedIndex + 1]['title'], 'body_text' => $versions[$selectedIndex + 1]['body_text']]
                : null,
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Article, $found),
        ]);
    }

    /**
     * @return array{id: null, label: string, title: string, summary: ?string, body: string, body_text: string, change_note: ?string, edited_by_name: ?string, saved_at: ?string, is_current: true}
     */
    private static function current(Article $article): array
    {
        return [
            'id' => null,
            'label' => (string) $article->versionLabel(),
            'title' => $article->getTitle()->toString(),
            'summary' => $article->getSummary()->toNullableString(),
            'body' => $article->getBody()->toString(),
            'body_text' => $article->getBodyText()->toString(),
            'change_note' => $article->getChangeNote()->toNullableString(),
            'edited_by_name' => $article->getEditedBy()->toNullableData()?->getName()->toString(),
            'saved_at' => $article->getUpdatedAt()->toNullableISOString(),
            'is_current' => true,
        ];
    }

    /**
     * @return array{id: string, label: string, title: string, summary: ?string, body: string, body_text: string, change_note: ?string, edited_by_name: ?string, saved_at: ?string, is_current: false}
     */
    private static function earlier(ArticleVersion $version): array
    {
        return [
            'id' => $version->getId()->toString(),
            'label' => $version->versionLabel(),
            'title' => $version->getTitle()->toString(),
            'summary' => $version->getSummary()->toNullableString(),
            'body' => $version->getBody()->toString(),
            'body_text' => $version->getBodyText()->toString(),
            'change_note' => $version->getChangeNote()->toNullableString(),
            'edited_by_name' => $version->getEditedBy()->toNullableData()?->getName()->toString(),
            'saved_at' => $version->getSavedAt()->toNullableISOString(),
            'is_current' => false,
        ];
    }
}
