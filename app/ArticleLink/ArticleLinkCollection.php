<?php

declare(strict_types=1);

namespace App\ArticleLink;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, ArticleLink>
 */
final class ArticleLinkCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return ArticleLink::class;
    }
}
