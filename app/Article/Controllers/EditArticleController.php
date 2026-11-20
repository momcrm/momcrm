<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\Actions\FindArticle;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleVisibility;
use App\Article\Exceptions\CouldNotFindArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class EditArticleController
{
    /**
     * @throws CouldNotFindArticle
     */
    public function __invoke(Request $request, string $article): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $userId = (string) $request->user()?->id;
        $found = FindArticle::visibleByIdForTeam($article, $teamId, $userId);

        if ($found->isNull() || $found->isArchived()) {
            throw CouldNotFindArticle::notFound();
        }

        Gate::authorize('update', $found);

        return Inertia::render('articles/edit', [
            'article' => $found->toDetail(),
            'classifications' => ArticleClassificationEnum::optionsFor(ArticleVisibility::roleOf($teamId, $userId)),
        ]);
    }
}
