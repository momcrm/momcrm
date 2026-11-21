<?php

declare(strict_types=1);

namespace App\Portal;

/**
 * Session keys for the customer portal.
 */
final class PortalSession
{
    /**
     * The staff member viewing the portal as a contact. While it is set the
     * portal is read-only.
     */
    public const string VIEWING_AS = 'portal.viewing_as';
}
