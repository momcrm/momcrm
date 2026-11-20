<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\ArticleCategory\ArticleCategory;
use App\Shared\Abstracts\AbstractRelatedData;

/**
 * The category the article is filed in, when loaded.
 *
 * @extends AbstractRelatedData<ArticleCategory>
 */
final class Category extends AbstractRelatedData
{
    public static function getName(): string
    {
        return 'category';
    }

    public static function dataClass(): string
    {
        return ArticleCategory::class;
    }
}
