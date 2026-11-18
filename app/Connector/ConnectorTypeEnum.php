<?php

declare(strict_types=1);

namespace App\Connector;

use App\Shared\Traits\Enum;
use App\SocialAccount\SocialProviderEnum;

/**
 * The kinds of outside account a team can connect. Each says what it can be
 * used for; nothing else in the app needs to know one type from another.
 */
enum ConnectorTypeEnum: string
{
    use Enum;

    case Microsoft = 'microsoft';
    case Google = 'google';
    case Imap = 'imap';

    public function label(): string
    {
        return match ($this) {
            self::Microsoft => 'Microsoft 365',
            self::Google => 'Google',
            self::Imap => 'IMAP/SMTP',
        };
    }

    /**
     * What it connects, in a line, for the page that offers it.
     */
    public function description(): string
    {
        return match ($this) {
            self::Microsoft => 'Outlook and Exchange Online mailboxes, including shared mailboxes.',
            self::Google => 'Gmail and Google Workspace mailboxes.',
            self::Imap => 'Any other mail provider, by its IMAP and SMTP server settings.',
        };
    }

    /**
     * Who runs the service, as error messages name it.
     */
    public function company(): string
    {
        return match ($this) {
            self::Microsoft => 'Microsoft',
            self::Google => 'Google',
            self::Imap => 'The mail server',
        };
    }

    /**
     * @return list<ConnectorCapabilityEnum>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::Microsoft, self::Google, self::Imap => [ConnectorCapabilityEnum::Mail],
        };
    }

    public function hasCapability(ConnectorCapabilityEnum $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * The sign-in provider whose keys this type connects with; none for a
     * type that is connected by filling in a form.
     */
    public function signInProvider(): ?SocialProviderEnum
    {
        return match ($this) {
            self::Microsoft => SocialProviderEnum::Microsoft,
            self::Google => SocialProviderEnum::Google,
            self::Imap => null,
        };
    }

    /**
     * Whether an account is connected by signing in at the service, and not
     * by typing in where its servers are.
     */
    public function usesSignIn(): bool
    {
        return $this->signInProvider() !== null;
    }

    /**
     * Whether this installation is set up to connect accounts of this type.
     */
    public function isAvailable(): bool
    {
        return $this->signInProvider()?->isConfigured() ?? true;
    }

    /**
     * What a person is asked to agree to when connecting. Shared mailboxes
     * need their own permissions, which only work and school accounts have,
     * so they are asked for only when wanted.
     *
     * @return list<string>
     */
    public function scopes(bool $withSharedMailboxes = false): array
    {
        return match ($this) {
            self::Microsoft => $withSharedMailboxes
                ? ['offline_access', 'User.Read', 'Mail.ReadWrite.Shared', 'Mail.Send.Shared']
                : ['offline_access', 'User.Read', 'Mail.ReadWrite', 'Mail.Send'],
            self::Google => [
                'https://www.googleapis.com/auth/gmail.readonly',
                'https://www.googleapis.com/auth/gmail.send',
            ],
            self::Imap => [],
        };
    }

    /**
     * What the sign-in page is told besides the permissions: to let the
     * person choose the account, and, at Google, to give lasting access even
     * when they have agreed before.
     *
     * @return array<string, string>
     */
    public function signInParameters(): array
    {
        return match ($this) {
            self::Microsoft => ['prompt' => 'select_account'],
            self::Google => ['access_type' => 'offline', 'prompt' => 'consent select_account'],
            self::Imap => [],
        };
    }

    /**
     * Whether an account of this type can open mailboxes other than its own.
     */
    public function supportsSharedMailboxes(): bool
    {
        return match ($this) {
            self::Microsoft => true,
            self::Google, self::Imap => false,
        };
    }

    /**
     * Where a stored refresh token is exchanged for an access token; nowhere
     * for a type that has no tokens.
     */
    public function tokenUrl(): ?string
    {
        return match ($this) {
            self::Microsoft => sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
                config()->string('services.microsoft.tenant', 'common'),
            ),
            self::Google => 'https://oauth2.googleapis.com/token',
            self::Imap => null,
        };
    }

    /**
     * Every type, for the page that offers them, with whether this
     * installation is set up to connect it.
     *
     * @return list<array{value: string, label: string, description: string, capabilities: list<string>, supports_shared_mailboxes: bool, uses_sign_in: bool, available: bool}>
     */
    public static function catalog(): array
    {
        return array_values(array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'capabilities' => array_map(fn (ConnectorCapabilityEnum $capability): string => $capability->label(), $type->capabilities()),
                'supports_shared_mailboxes' => $type->supportsSharedMailboxes(),
                'uses_sign_in' => $type->usesSignIn(),
                'available' => $type->isAvailable(),
            ],
            self::cases(),
        ));
    }
}
