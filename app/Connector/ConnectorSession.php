<?php

declare(strict_types=1);

namespace App\Connector;

/**
 * Session keys used while someone is away signing in to connect an account.
 */
final class ConnectorSession
{
    /**
     * The keys of the options chosen for the sign-in under way, such as
     * access to shared mailboxes.
     */
    public const string PENDING_OPTIONS = 'connector.pending_options';
}
