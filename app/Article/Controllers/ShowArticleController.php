<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\Exceptions\CouldNotFindArticle;
use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleLink\Actions\FindArticleLink;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ShowArticleController
{
    /**
     * @throws CouldNotFindArticle
     */
    public function __invoke(Request $request, string $article): Response
    {
        $found = FindArticle::visibleByIdForTeam($article, $request->user()?->current_team_id, (string) $request->user()?->id);

        if ($found->isNull()) {
            throw CouldNotFindArticle::notFound();
        }

        return Inertia::render('articles/show', [
            'article' => $found->toDetail(),
            'categoryPath' => self::categoryPath($found),
            'linkedTo' => FindArticleLink::recordsFor($found),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Article, $found),
        ]);
    }

    /**
     * The article's category with the ones above it, e.g. "Networking › Routers".
     */
    private static function categoryPath(Article $article): ?string
    {
        $categoryId = $article->getCategoryId()->toNullableString();

        if ($categoryId === null) {
            return null;
        }

        foreach (FindArticleCategory::listForTeam($article->getTeamId()->toString())->toSummaries() as $category) {
            if ($category['id'] === $categoryId) {
                return $category['path'];
            }
        }

        return null;
    }
}
