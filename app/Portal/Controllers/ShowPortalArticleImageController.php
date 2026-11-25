<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Article\ArticleImages;
use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Attachment;
use App\Attachment\AttachmentEloquentModel;
use App\Portal\Actions\FindPortalArticles;
use App\Shared\MorphMapKeyEnum;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A picture from a knowledge-base article the contact may read, served as
 * staff are served it: only picture types, never guessed or run.
 */
final class ShowPortalArticleImageController
{
    public function __invoke(string $article, string $image): StreamedResponse
    {
        $found = FindPortalArticles::visibleToContact($article);
        $attachment = Attachment::fromEloquentModel(AttachmentEloquentModel::query()
            ->withoutGlobalScopes()
            ->where('attachable_type', MorphMapKeyEnum::Article->value)
            ->where('attachable_id', $found->getId()->toString())
            ->whereKey($image)
            ->first());
        $path = $attachment->getPath()->toString();
        $mimeType = $attachment->getMimeType()->toString();

        if ($attachment->isNull() || ! in_array($mimeType, ArticleImages::MIME_TYPES, true) || ! Storage::disk(CreateAttachment::disk())->exists($path)) {
            throw new NotFoundHttpException;
        }

        return Storage::disk(CreateAttachment::disk())->response($path, $attachment->getName()->toString(), [
            'Content-Type' => $mimeType,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
