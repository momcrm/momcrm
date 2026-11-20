<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\Actions\FindArticle;
use App\Article\ArticleImages;
use App\Article\Exceptions\CouldNotFindArticle;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Actions\FindAttachment;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shows a picture from an article's body, to people who may read the
 * article. Only the picture types the editor takes are shown inline, and
 * the browser is told not to guess the type or run anything in it.
 */
final class ShowArticleImageController
{
    /**
     * @throws CouldNotFindArticle
     */
    public function __invoke(Request $request, string $article, string $image): StreamedResponse
    {
        $teamId = $request->user()?->current_team_id;
        $found = FindArticle::visibleByIdForTeam($article, $teamId, (string) $request->user()?->id);
        $attachment = FindAttachment::visibleByIdForTeam($image, $teamId, (string) $request->user()?->id);
        $path = $attachment->getPath()->toString();
        $mimeType = $attachment->getMimeType()->toString();

        if (
            $found->isNull()
            || $attachment->isNull()
            || $attachment->getAttachableType()->toNullableEnum() !== MorphMapKeyEnum::Article
            || ! $attachment->getAttachableId()->equalsTo($found->getId()->toString())
            || ! in_array($mimeType, ArticleImages::MIME_TYPES, true)
            || ! Storage::disk(CreateAttachment::DISK)->exists($path)
        ) {
            throw CouldNotFindArticle::notFound();
        }

        return Storage::disk(CreateAttachment::DISK)->response($path, $attachment->getName()->toString(), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
