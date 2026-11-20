<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\AccessRule\AccessResourceEnum;
use App\Article\Article;
use App\Portal\Actions\FindPortalArticles;
use App\Portal\PortalAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The knowledge base: how-tos and answers the team has written for its
 * customers, searchable by title.
 */
final class ListPortalArticlesController
{
    /**
     * @throws AuthorizationException
     */
    public function __invoke(Request $request): Response
    {
        PortalAccess::authorizeSection(AccessResourceEnum::Article);

        $search = $request->string('search')->trim()->toString() ?: null;

        return Inertia::render('portal/articles/index', [
            'search' => $search,
            'articles' => PortalAccess::visible(AccessResourceEnum::Article, FindPortalArticles::forContact($search))
                ->map(fn (Article $article): array => [
                    'id' => $article->getId()->toString(),
                    'title' => $article->getTitle()->toString(),
                    'summary' => $article->getSummary()->toNullableString(),
                    'is_for_customer' => $article->getCustomerId()->isNotNull(),
                    'updated_at' => $article->getUpdatedAt()->toNullableISOString(),
                ])
                ->values()
                ->all(),
        ]);
    }
}
