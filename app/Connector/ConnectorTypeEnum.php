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

    public function label(): string
    {
        return match ($this) {
            self::Microsoft => 'Microsoft 365',
        };
    }

    /**
     * The company people sign in with, as error messages name it.
     */
    public function company(): string
    {
        return match ($this) {
            self::Microsoft => 'Microsoft',
        };
    }

    /**
     * @return list<ConnectorCapabilityEnum>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::Microsoft => [ConnectorCapabilityEnum::Mail],
        };
    }

    public function hasCapability(ConnectorCapabilityEnum $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    /**
     * The sign-in provider whose keys this type connects with.
     */
    public function signInProvider(): SocialProviderEnum
    {
        return match ($this) {
            self::Microsoft => SocialProviderEnum::Microsoft,
        };
    }

    /**
     * Whether this installation is set up to connect accounts of this type.
     */
    public function isAvailable(): bool
    {
        return $this->signInProvider()->isConfigured();
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
        };
    }

    /**
     * Whether an account of this type can open mailboxes other than its own.
     */
    public function supportsSharedMailboxes(): bool
    {
        return match ($this) {
            self::Microsoft => true,
        };
    }

    /**
     * Where a stored refresh token is exchanged for an access token.
     */
    public function tokenUrl(): string
    {
        return match ($this) {
            self::Microsoft => sprintf(
                'https://login.microsoftonline.com/%s/oauth2/v2.0/token',
                config()->string('services.microsoft.tenant', 'common'),
            ),
        };
    }

    /**
     * The types this installation can connect, for the page that offers them.
     *
     * @return list<array{value: string, label: string, capabilities: list<string>, supports_shared_mailboxes: bool}>
     */
    public static function available(): array
    {
        return array_values(array_map(
            fn (self $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'capabilities' => array_map(fn (ConnectorCapabilityEnum $capability): string => $capability->label(), $type->capabilities()),
                'supports_shared_mailboxes' => $type->supportsSharedMailboxes(),
            ],
            array_filter(self::cases(), fn (self $type): bool => $type->isAvailable()),
        ));
    }
}
