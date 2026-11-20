<?php

declare(strict_types=1);

namespace App\Article;

/**
 * Pictures in an article's body. Each is a file attached to an article,
 * shown from that article's image address, so it is only served to people
 * who may read the article. A body may only show pictures from there.
 */
final class ArticleImages
{
    /** @var list<string> */
    public const array MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];

    public const int MAX_KILOBYTES = 10 * 1024;

    private const string PATH_PATTERN = '#^/articles/[0-9a-f-]{36}/images/[0-9a-f-]{36}$#i';

    /**
     * The address an image is shown from, relative so it survives a change
     * of domain.
     */
    public static function path(string $articleId, string $attachmentId): string
    {
        return route('articles.images.show', [$articleId, $attachmentId], absolute: false);
    }

    public static function isImagePath(string $src): bool
    {
        return preg_match(self::PATH_PATTERN, $src) === 1;
    }
}
