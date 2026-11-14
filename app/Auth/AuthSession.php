<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Session keys that carry someone through sign-in before they are logged in.
 */
final class AuthSession
{
    /**
     * The address a code was just emailed to.
     */
    public const string PENDING_EMAIL = 'auth.pending_email';

    /**
     * An address proven by a code (or a verified Google account) with no account yet.
     */
    public const string VERIFIED_EMAIL = 'auth.verified_email';

    /**
     * A suggested name for the new account, from Google or Microsoft.
     */
    public const string SUGGESTED_NAME = 'auth.suggested_name';

    /**
     * A Google or Microsoft account waiting to be linked once the email is proven:
     * array{provider: string, provider_user_id: string, email: ?string}.
     */
    public const string PENDING_SOCIAL = 'auth.pending_social';

    /**
     * The key Laravel's RequirePassword middleware reads to know someone recently confirmed it's them.
     */
    public const string PASSWORD_CONFIRMED_AT = 'auth.password_confirmed_at';
}
