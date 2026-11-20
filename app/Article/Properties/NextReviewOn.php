<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * When the article should next be checked it is still right.
 */
final class NextReviewOn extends AbstractDateOnly
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'next_review_on';
    }
}
