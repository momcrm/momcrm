<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The category the article is filed in, if any.
 */
final class CategoryId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'category_id';
    }
}
