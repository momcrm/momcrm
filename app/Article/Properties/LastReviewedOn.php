<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractDateOnly;

/**
 * When someone last checked the article was still right.
 */
final class LastReviewedOn extends AbstractDateOnly
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_reviewed_on';
    }
}
