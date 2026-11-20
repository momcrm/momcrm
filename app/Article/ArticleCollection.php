<?php

declare(strict_types=1);

namespace App\Article;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, Article>
 */
final class ArticleCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return Article::class;
    }
}
