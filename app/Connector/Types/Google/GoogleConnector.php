<?php

declare(strict_types=1);

namespace App\Connector\Types\Google;

use Mom\Connectors\Connection;
use Mom\Connectors\ConnectorType;
use Mom\Connectors\ConnectsBySignIn;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\ProvidesMail;

/**
 * A Gmail or Google Workspace account, connected by signing in with Google.
 * Its mailbox is reached through the Gmail API.
 */
final class GoogleConnector extends ConnectorType implements ConnectsBySignIn, ProvidesMail
{
    public const string KEY = 'google';

    public const string COMPANY = 'Google';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Google';
    }

    public function description(): string
    {
        return 'Gmail and Google Workspace mailboxes.';
    }

    public function signInDriver(): string
    {
        return 'google';
    }

    public function signInOptions(): array
    {
        return [];
    }

    public function scopes(array $options): array
    {
        return [
            'https://www.googleapis.com/auth/gmail.readonly',
            'https://www.googleapis.com/auth/gmail.send',
        ];
    }

    /**
     * Google gives lasting access only when asked for it, and only on a
     * fresh agreement, so the person is asked to agree each time.
     */
    public function signInParameters(): array
    {
        return ['access_type' => 'offline', 'prompt' => 'consent select_account'];
    }

    public function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    public function mailClient(Connection $connection, ?string $sharedAddress = null): MailClient
    {
        return GoogleMailClient::forConnection($connection);
    }

    /**
     * Google has no shared mailboxes to open through someone else's sign-in.
     */
    public function opensSharedMailboxes(Connection $connection): bool
    {
        return false;
    }
}
