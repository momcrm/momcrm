<?php

declare(strict_types=1);

namespace App\Rate\Properties;

use App\Shared\Abstracts\AbstractUuid;

/**
 * The rate this one sits under in the price list, if any. Parents are rates
 * in their own right, with their own prices.
 */
final class ParentId extends AbstractUuid
{
    public static function getName(): string
    {
        return 'parent_id';
    }
}
