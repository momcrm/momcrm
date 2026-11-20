<?php

declare(strict_types=1);

namespace App\ArticleLink\Controllers;

use App\Article\Actions\FindArticle;
use App\Article\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Articles to attach to a record: ones the person may read that aren't
 * archived, matching what they typed, most recently changed first.
 */
final class ListArticleLinkOptionsController
{
    private const int LIMIT = 10;

    public function __invoke(Request $request): JsonResponse
    {
        $articles = FindArticle::searchVisible(
            (string) $request->user()?->current_team_id,
            (string) $request->user()?->id,
            $request->string('q')->trim()->toString(),
            self::LIMIT,
        );

        return response()->json([
            'articles' => $articles->map(fn (Article $article): array => [
                'id' => $article->getId()->toString(),
                'title' => $article->getTitle()->toString(),
                'summary' => $article->getSummary()->toNullableString(),
                'status' => $article->getStatus()->toEnum()->value,
            ])->values()->all(),
        ]);
    }
}
