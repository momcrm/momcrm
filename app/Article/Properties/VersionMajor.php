<?php

declare(strict_types=1);

namespace App\Article\Properties;

use Mom\Data\AbstractInteger;

final class VersionMajor extends AbstractInteger
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'version_major';
    }
}
