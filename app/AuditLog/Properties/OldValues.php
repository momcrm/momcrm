<?php

declare(strict_types=1);

namespace App\AuditLog\Properties;

use Mom\Data\AbstractCollection;

final class OldValues extends AbstractCollection
{
    public static function getName(): string
    {
        return 'old_values';
    }
}
