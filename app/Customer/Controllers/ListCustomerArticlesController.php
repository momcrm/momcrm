<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\AccessRule\Engine\AccessAbilities;
use App\Article\Actions\FindArticle;
use App\Article\Article;
use App\Article\ArticleClassificationEnum;
use App\Article\ArticleVisibility;
use App\ArticleCategory\Actions\FindArticleCategory;
use App\Customer\CustomerWorkspace;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer's articles tab: the knowledge base articles about this
 * customer that the person may read, newest change first.
 */
final class ListCustomerArticlesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $workspace = CustomerWorkspace::open($request, $customer);
        $search = $request->string('search')->trim()->toString() ?: null;
        $sort = in_array($request->string('sort')->toString(), FindArticle::SORTS, true) ? $request->string('sort')->toString() : 'updated';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        return Inertia::render('customers/workspace/articles', [
            ...$workspace->props($request),
            'articles' => FindArticle::paginateVisible($workspace->teamId, $workspace->userId, $search, customerId: $workspace->customerId(), sort: $sort, direction: $direction)
                ->through(fn (Article $article): array => $article->toSummary()),
            'filters' => ['search' => $search, 'sort' => $sort, 'direction' => $direction],
            'categories' => FindArticleCategory::listForTeam($workspace->teamId)->toSummaries(),
            'classifications' => ArticleClassificationEnum::optionsFor(ArticleVisibility::roleOf($workspace->teamId, $workspace->userId)),
            'articleCan' => AccessAbilities::for($request->user(), AccessResourceEnum::Article),
        ]);
    }
}
