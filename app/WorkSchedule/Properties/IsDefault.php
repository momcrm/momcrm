<?php

declare(strict_types=1);

namespace App\WorkSchedule\Properties;

use Mom\Data\AbstractBoolean;

/**
 * Whether members without a schedule of their own follow this one. A team
 * has at most one default.
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
