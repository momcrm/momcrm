<?php

declare(strict_types=1);

namespace App\Article\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The customer the article is about, if it isn't for the whole team.
 */
final class CustomerId extends AbstractUuid
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'customer_id';
    }
}
