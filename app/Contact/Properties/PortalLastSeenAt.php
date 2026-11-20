<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use Mom\Data\AbstractDate;

/**
 * When the contact last used the customer portal, to within a few minutes.
 */
final class PortalLastSeenAt extends AbstractDate
{
    public static bool $includeInDatabaseUpdate = false;

    public static function getName(): string
    {
        return 'portal_last_seen_at';
    }
}
