<?php

declare(strict_types=1);

namespace App\ArticleVersion;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, ArticleVersion>
 */
final class ArticleVersionCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return ArticleVersion::class;
    }
}
