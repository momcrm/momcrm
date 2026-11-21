<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\Actions\FindPortalArticles;
use Inertia\Inertia;
use Inertia\Response;

/**
 * One knowledge-base article. Its pictures are served through the portal,
 * as the staff addresses in its body are for the team only.
 */
final class ShowPortalArticleController
{
    public function __invoke(string $article): Response
    {
        $found = FindPortalArticles::visibleToContact($article);

        return Inertia::render('portal/articles/show', [
            'article' => [
                'id' => $found->getId()->toString(),
                'title' => $found->getTitle()->toString(),
                'summary' => $found->getSummary()->toNullableString(),
                'body' => $found->getBody()->toNullableString() ?? '',
                'updated_at' => $found->getUpdatedAt()->toNullableISOString(),
            ],
        ]);
    }
}
