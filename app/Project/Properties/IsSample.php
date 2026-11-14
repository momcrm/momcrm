<?php

declare(strict_types=1);

namespace App\Project\Properties;

use Mom\Data\AbstractBoolean;

final class IsSample extends AbstractBoolean
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'is_sample';
    }

    public function default(): bool
    {
        return false;
    }
}
