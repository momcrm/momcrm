<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractDate;

/**
 * When the inbox was last read successfully.
 */
final class LastSyncedAt extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'last_synced_at';
    }
}
