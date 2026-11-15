<?php

declare(strict_types=1);

namespace App\Choice\Properties;

use Mom\Data\AbstractBoolean;

/**
 * Whether new records start with this choice. Lists with categories have one
 * default per category, e.g. the status a ticket gets when it is closed.
 */
final class IsDefault extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'is_default';
    }

    public function default(): bool
    {
        return false;
    }
}
