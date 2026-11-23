<?php

declare(strict_types=1);

namespace App\ShiftBreak\Properties;

use App\Shared\Abstracts\AbstractUuid;

final class ShiftId extends AbstractUuid
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'shift_id';
    }
}
