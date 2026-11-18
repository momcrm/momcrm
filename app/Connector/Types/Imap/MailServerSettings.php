<?php

declare(strict_types=1);

namespace App\Connector\Types\Imap;

use Mom\Connectors\Connection;

/**
 * Where a mailbox's IMAP and SMTP servers are and how to sign in to them:
 * what an IMAP/SMTP connector keeps. The passwords come from the connector's
 * encrypted credentials, the rest from its settings.
 */
final readonly class MailServerSettings
{
    public const string ENCRYPTION_SSL = 'ssl';

    public const string ENCRYPTION_STARTTLS = 'starttls';

    public const string ENCRYPTION_NONE = 'none';

    public const array ENCRYPTIONS = [self::ENCRYPTION_SSL, self::ENCRYPTION_STARTTLS, self::ENCRYPTION_NONE];

    /**
     * The settings a connector stores, which are the fields of this object
     * that are not secret.
     */
    public const array SETTINGS = [
        'from_name', 'imap_host', 'imap_port', 'imap_encryption', 'imap_username',
        'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username',
    ];

    public const string IMAP_PASSWORD = 'imap_password';

    public const string SMTP_PASSWORD = 'smtp_password';

    public function __construct(
        public string $fromAddress,
        public ?string $fromName,
        public string $imapHost,
        public int $imapPort,
        public string $imapEncryption,
        public string $imapUsername,
        public string $imapPassword,
        public string $smtpHost,
        public int $smtpPort,
        public string $smtpEncryption,
        public string $smtpUsername,
        public string $smtpPassword,
    ) {}

    /**
     * @param  array<string, mixed>  $values  the address, settings and passwords by name
     */
    public static function fromArray(array $values): self
    {
        $text = fn (string $key): string => is_string($values[$key] ?? null) ? $values[$key] : '';
        $number = fn (string $key): int => is_numeric($values[$key] ?? null) ? (int) $values[$key] : 0;

        return new self(
            fromAddress: mb_strtolower(mb_trim($text('from_address'))),
            fromName: $text('from_name') === '' ? null : $text('from_name'),
            imapHost: mb_trim($text('imap_host')),
            imapPort: $number('imap_port'),
            imapEncryption: $text('imap_encryption'),
            imapUsername: $text('imap_username'),
            imapPassword: $text(self::IMAP_PASSWORD),
            smtpHost: mb_trim($text('smtp_host')),
            smtpPort: $number('smtp_port'),
            smtpEncryption: $text('smtp_encryption'),
            smtpUsername: $text('smtp_username'),
            smtpPassword: $text(self::SMTP_PASSWORD),
        );
    }

    /**
     * What a connected IMAP/SMTP account holds: its settings, its encrypted
     * passwords and the address it is known by.
     */
    public static function fromConnection(Connection $connection): self
    {
        return self::fromArray([
            ...$connection->settings(),
            ...$connection->credentials(),
            'from_address' => $connection->account(),
        ]);
    }

    /**
     * The part a connector keeps in its settings.
     *
     * @return array<string, int|string|null>
     */
    public function toSettings(): array
    {
        return [
            'from_name' => $this->fromName,
            'imap_host' => $this->imapHost,
            'imap_port' => $this->imapPort,
            'imap_encryption' => $this->imapEncryption,
            'imap_username' => $this->imapUsername,
            'smtp_host' => $this->smtpHost,
            'smtp_port' => $this->smtpPort,
            'smtp_encryption' => $this->smtpEncryption,
            'smtp_username' => $this->smtpUsername,
        ];
    }

    /**
     * The part a connector keeps encrypted.
     *
     * @return array<string, string>
     */
    public function toCredentials(): array
    {
        return [self::IMAP_PASSWORD => $this->imapPassword, self::SMTP_PASSWORD => $this->smtpPassword];
    }
}
