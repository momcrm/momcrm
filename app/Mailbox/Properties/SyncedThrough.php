<?php

declare(strict_types=1);

namespace App\Mailbox\Properties;

use Mom\Data\AbstractDate;

/**
 * How far the inbox has been read: mail received before this has been seen.
 * It starts when the mailbox is connected, so older mail is never imported.
 */
final class SyncedThrough extends AbstractDate
{
    public static bool $includeInEloquentFactory = false;

    public static function getName(): string
    {
        return 'synced_through';
    }
}
