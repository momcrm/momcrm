<?php

declare(strict_types=1);

namespace App\Contact\Properties;

use Mom\Data\AbstractDate;

/**
 * When the contact was last emailed an invitation to the customer portal.
 */
final class PortalInvitedAt extends AbstractDate
{
    public static function getName(): string
    {
        return 'portal_invited_at';
    }
}
