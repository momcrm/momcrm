<?php

declare(strict_types=1);

namespace App\User\Properties;

use Mom\Data\AbstractBoolean;

/**
 * Whether the person looks after the installation itself, such as its `.env`,
 * rather than any one team.
 */
final class IsPlatformAdmin extends AbstractBoolean
{
    public static function getName(): string
    {
        return 'is_platform_admin';
    }

    public function default(): bool
    {
        return false;
    }
}
