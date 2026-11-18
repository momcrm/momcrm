<?php

declare(strict_types=1);

namespace App\Connector;

/**
 * Session keys used while someone is away signing in to connect an account.
 */
final class ConnectorSession
{
    /**
     * Whether the sign-in under way asked for access to shared mailboxes.
     */
    public const string PENDING_SHARED_MAILBOXES = 'connector.pending_shared_mailboxes';
}
