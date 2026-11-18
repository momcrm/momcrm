<?php

declare(strict_types=1);

namespace App\Connector\Types\Microsoft;

use App\Connector\Connector;
use App\Mailbox\Contracts\MailClient;
use App\Mailbox\Contracts\ProvidesMail;
use Mom\Connectors\ConnectorType;
use Mom\Connectors\ConnectsBySignIn;
use Mom\Connectors\SignInOption;

/**
 * A Microsoft 365 account, connected by signing in with Microsoft. Its
 * mailbox, and the shared mailboxes it can open, are reached through
 * Microsoft Graph.
 */
final class MicrosoftConnector extends ConnectorType implements ConnectsBySignIn, ProvidesMail
{
    public const string KEY = 'microsoft';

    public const string COMPANY = 'Microsoft';

    /**
     * The option, and the setting it leaves on the connector, that says the
     * sign-in covers shared mailboxes.
     */
    public const string SHARED_MAILBOXES = 'shared_mailboxes';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'Microsoft 365';
    }

    public function description(): string
    {
        return 'Outlook and Exchange Online mailboxes, including shared mailboxes.';
    }

    public function company(): string
    {
        return self::COMPANY;
    }

    public function signInDriver(): string
    {
        return 'microsoft';
    }

    public function signInOptions(): array
    {
        return [
            new SignInOption(
                self::SHARED_MAILBOXES,
                'This account opens shared mailboxes',
                'Such as support@yourcompany.com. Work and school accounts only.',
                'Shared mailboxes',
            ),
        ];
    }

    /**
     * Shared mailboxes need their own permissions, which only work and
     * school accounts have, so they are asked for only when wanted.
     */
    public function scopes(array $options): array
    {
        return in_array(self::SHARED_MAILBOXES, $options, true)
            ? ['offline_access', 'User.Read', 'Mail.ReadWrite.Shared', 'Mail.Send.Shared']
            : ['offline_access', 'User.Read', 'Mail.ReadWrite', 'Mail.Send'];
    }

    public function signInParameters(): array
    {
        return ['prompt' => 'select_account'];
    }

    public function tokenUrl(): string
    {
        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
            config()->string('services.microsoft.tenant', 'common'),
        );
    }

    public function mailClient(Connector $connector, ?string $sharedAddress = null): MailClient
    {
        return new MicrosoftMailClient(MicrosoftGraph::forConnector($connector, $sharedAddress));
    }

    public function opensSharedMailboxes(Connector $connector): bool
    {
        return $connector->hasOption(self::SHARED_MAILBOXES);
    }
}
