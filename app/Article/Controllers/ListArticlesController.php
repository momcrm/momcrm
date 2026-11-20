<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleStatusEnum;
use App\Article\ArticleVisibility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The knowledge base: the team's articles someone may read, newest change
 * first, with search and filters.
 */
final class ListArticlesController
{
    public function __invoke(Request $request): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $userId = (string) $request->user()?->id;
        $search = $request->string('search')->trim()->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $classification = ArticleClassificationEnum::tryFrom($request->string('classification')->toString())?->value;
        $sort = in_array($request->string('sort')->toString(), FindArticle::SORTS, true) ? $request->string('sort')->toString() : 'updated';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        return Inertia::render('articles/index', [
            'articles' => FindArticle::paginateVisible($teamId, $userId, $search, $status, $classification, $sort, $direction)
                ->through(fn (Article $article): array => $article->toSummary()),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'classification' => $classification,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statuses' => [...ArticleStatusEnum::options(), ['value' => FindArticle::ARCHIVED, 'label' => 'Archived']],
            'classifications' => ArticleClassificationEnum::optionsFor(ArticleVisibility::roleOf($teamId, $userId)),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Article),
        ]);
    }
}
