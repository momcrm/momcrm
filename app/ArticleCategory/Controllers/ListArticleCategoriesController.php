<?php

declare(strict_types=1);

namespace App\ArticleCategory\Controllers;

use App\ArticleCategory\Actions\FindArticleCategory;
use App\ArticleCategory\ArticleCategoryAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The knowledge base's categories in tree order, for owners and admins to keep.
 */
final class ListArticleCategoriesController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        ArticleCategoryAccess::authorize($request);

        return Inertia::render('settings/knowledge-base', [
            'categories' => FindArticleCategory::listForTeam(ArticleCategoryAccess::team($request)->getId()->toString())->toSummaries(),
        ]);
    }
}
