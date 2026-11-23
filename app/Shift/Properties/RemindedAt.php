<?php

declare(strict_types=1);

namespace App\Shift\Properties;

use Mom\Data\AbstractDate;

/**
 * When the member was told the shift is still open, so they are told once.
 */
final class RemindedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'reminded_at';
    }
}
