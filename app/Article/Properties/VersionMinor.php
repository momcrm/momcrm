<?php

declare(strict_types=1);

namespace App\Article\Properties;

use Mom\Data\AbstractInteger;

final class VersionMinor extends AbstractInteger
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'version_minor';
    }
}
