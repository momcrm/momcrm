<?php

declare(strict_types=1);

namespace App\Portal;

/**
 * Session keys that carry a contact through signing in to the customer
 * portal. They are kept apart from staff sign-in's, as one browser can be
 * signed in to both.
 */
final class PortalSession
{
    /**
     * The address a portal code was just emailed to.
     */
    public const string PENDING_EMAIL = 'portal.pending_email';

    /**
     * An address proven by a code or Google, while its owner picks which of
     * their customers to sign in as.
     */
    public const string VERIFIED_EMAIL = 'portal.verified_email';

    /**
     * Set while someone is away signing in with Google or Microsoft for the
     * portal, so the shared callback knows where they came from.
     */
    public const string SOCIAL = 'portal.social';

    /**
     * The portal page a signed-out contact asked for, to return to once in.
     */
    public const string INTENDED = 'portal.intended';

    /**
     * The staff member viewing the portal as a contact. While it is set the
     * portal is read-only.
     */
    public const string VIEWING_AS = 'portal.viewing_as';
}
