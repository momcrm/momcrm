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
use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\ArticleCategory;
use App\Customer\Actions\FindCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The knowledge base: the team's articles someone may read, newest change
 * first, with search and filters. Filtering by a category includes the
 * categories under it.
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
        $categories = FindArticleCategory::listForTeam($teamId);
        $category = $request->string('category')->toString() ?: null;
        $categoryIds = $category !== null && $categories->contains(fn (ArticleCategory $known): bool => $known->getId()->toString() === $category)
            ? $categories->withDescendantIds($category)
            : null;
        $label = $request->string('label')->toString() ?: null;
        $isReviewDue = $request->string('review')->toString() === 'due';
        $customer = Str::isUuid($request->string('customer')->toString()) ? $request->string('customer')->toString() : null;

        return Inertia::render('articles/index', [
            'articles' => FindArticle::paginateVisible($teamId, $userId, $search, $status, $classification, $categoryIds, $label, $isReviewDue, $customer, $sort, $direction)
                ->through(fn (Article $article): array => $article->toSummary()),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'classification' => $classification,
                'category' => $categoryIds === null ? null : $category,
                'label' => $label,
                'review' => $isReviewDue ? 'due' : null,
                'customer' => $customer,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statuses' => [...ArticleStatusEnum::options(), ['value' => FindArticle::ARCHIVED, 'label' => 'Archived']],
            'categories' => $categories->toSummaries(),
            'customers' => FindCustomer::optionsForTeam($teamId),
            'classifications' => ArticleClassificationEnum::optionsFor(ArticleVisibility::roleOf($teamId, $userId)),
            'can' => AccessAbilities::for($request->user(), AccessResourceEnum::Article),
        ]);
    }
}
