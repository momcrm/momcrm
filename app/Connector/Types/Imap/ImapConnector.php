<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use Mom\Connectors\ConnectedAccount;
use Mom\Connectors\Connection;
use Mom\Connectors\ConnectorType;
use Mom\Connectors\ConnectsByForm;
use Mom\Connectors\CouldNotConnect;
use Mom\Connectors\CouldNotReachConnector;
use Mom\Connectors\Field;
use Mom\Connectors\Mail\MailClient;
use Mom\Connectors\Mail\ProvidesMail;
use Mom\Connectors\PublicHost;

/**
 * A mailbox on any mail server, connected by typing in where its IMAP and
 * SMTP servers are and how to sign in to them.
 */
final class ImapConnector extends ConnectorType implements ConnectsByForm, ProvidesMail
{
    public const string KEY = 'imap';

    public const string COMPANY = 'The mail server';

    private const string INCOMING = 'Incoming mail (IMAP)';

    private const string OUTGOING = 'Outgoing mail (SMTP)';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return 'IMAP/SMTP';
    }

    public function description(): string
    {
        return 'Any other mail provider, by its IMAP and SMTP server settings.';
    }

    public function company(): string
    {
        return self::COMPANY;
    }

    public function formIntroduction(): string
    {
        return 'MomCRM reads the mailbox over IMAP and sends replies over SMTP. Your mail provider lists these settings, often under “mail client setup”.';
    }

    public function fields(): array
    {
        $encryptions = [
            MailServerSettings::ENCRYPTION_SSL => 'SSL/TLS',
            MailServerSettings::ENCRYPTION_STARTTLS => 'STARTTLS',
            MailServerSettings::ENCRYPTION_NONE => 'None',
        ];
        $host = fn (string $name, string $example): Field => Field::text($name, 'Server')
            ->required()
            ->span(3)
            ->placeholder($example)
            ->rules(['regex:/^[A-Za-z0-9.\-:\[\]]+$/'])
            ->message('regex', (string) __('Enter the server\'s name, such as :example, without "https://" or a path.', ['example' => $example]));
        $port = fn (string $name): Field => Field::number($name, 'Port')->required()->span(1)->rules(['between:1,65535']);

        return [
            Field::email('from_address', 'Email address')->required()->span(3)->placeholder('support@yourcompany.com'),
            Field::text('from_name', 'Name customers see')->span(3)->placeholder('Optional'),

            $host('imap_host', 'imap.yourcompany.com')->group(self::INCOMING),
            $port('imap_port')->group(self::INCOMING)->default(993),
            Field::select('imap_encryption', 'Encryption', $encryptions)->required()->group(self::INCOMING)->span(2)->default(MailServerSettings::ENCRYPTION_SSL),
            Field::text('imap_username', 'Username')->required()->group(self::INCOMING)->span(3),
            Field::password(MailServerSettings::IMAP_PASSWORD, 'Password')->required()->group(self::INCOMING)->span(3),

            $host('smtp_host', 'smtp.yourcompany.com')->group(self::OUTGOING),
            $port('smtp_port')->group(self::OUTGOING)->default(587),
            Field::select('smtp_encryption', 'Encryption', $encryptions)->required()->group(self::OUTGOING)->span(2)->default(MailServerSettings::ENCRYPTION_STARTTLS),
            Field::text('smtp_username', 'Username')->group(self::OUTGOING)->span(3)->hint('Leave empty to use the incoming one.'),
            Field::password(MailServerSettings::SMTP_PASSWORD, 'Password')->group(self::OUTGOING)->span(3)->hint('Leave empty to use the incoming one.'),
        ];
    }

    public function accountField(): string
    {
        return 'from_address';
    }

    /**
     * Signs in to both servers before anything is saved, so a connector that
     * cannot read or cannot send is never listed.
     */
    public function connect(array $values): ConnectedAccount
    {
        $settings = MailServerSettings::fromArray([
            ...$values,
            'smtp_username' => filled($values['smtp_username'] ?? null) ? $values['smtp_username'] : ($values['imap_username'] ?? null),
            'smtp_password' => filled($values['smtp_password'] ?? null) ? $values['smtp_password'] : ($values['imap_password'] ?? null),
        ]);

        foreach (['imap_host' => $settings->imapHost, 'smtp_host' => $settings->smtpHost] as $field => $host) {
            if (! PublicHost::allows($host)) {
                throw CouldNotConnect::because($field, (string) __("MomCRM can't connect to :host. Check the name, and that the server can be reached from the internet.", ['host' => $host]));
            }
        }

        $servers = app(MailServers::class);

        try {
            $servers->inbox($settings);
        } catch (CouldNotReachConnector $exception) {
            throw self::refused($exception, 'imap', 'IMAP', $settings->imapHost, $settings->imapPort);
        }

        try {
            $servers->checkSmtp($settings);
        } catch (CouldNotReachConnector $exception) {
            throw self::refused($exception, 'smtp', 'SMTP', $settings->smtpHost, $settings->smtpPort);
        }

        return new ConnectedAccount(
            account: $settings->fromAddress,
            name: $settings->fromName ?? $settings->fromAddress,
            settings: $settings->toSettings(),
            credentials: $settings->toCredentials(),
        );
    }

    public function mailClient(Connection $connection, ?string $sharedAddress = null): MailClient
    {
        return ImapMailClient::forConnection($connection);
    }

    /**
     * A sign-in on a mail server reaches one mailbox: its own.
     */
    public function opensSharedMailboxes(Connection $connection): bool
    {
        return false;
    }

    /**
     * Why a server would not let us in, against the field to look at.
     */
    private static function refused(CouldNotReachConnector $exception, string $prefix, string $protocol, string $host, int $port): CouldNotConnect
    {
        return match (true) {
            $exception->needsReconnect => CouldNotConnect::because("{$prefix}_password", (string) __('The :protocol server did not accept that username and password.', ['protocol' => $protocol])),
            $exception->isTemporary => CouldNotConnect::because("{$prefix}_host", (string) __("We couldn't reach the :protocol server at :host on port :port. Check the server, port and encryption.", ['protocol' => $protocol, 'host' => $host, 'port' => $port])),
            default => CouldNotConnect::because("{$prefix}_host", $exception->getMessage()),
        };
    }
}
