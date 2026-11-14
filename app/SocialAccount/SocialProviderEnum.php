<?php

declare(strict_types=1);

namespace App\SocialAccount;

use App\Shared\Traits\Enum;

enum SocialProviderEnum: string
{
    use Enum;

    case Google = 'google';
    case Microsoft = 'microsoft';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Microsoft => 'Microsoft',
        };
    }

    /**
     * Whether the provider's OAuth keys are set, so its button can be offered.
     */
    public function isConfigured(): bool
    {
        return filled(config("services.{$this->value}.client_id"))
            && filled(config("services.{$this->value}.client_secret"));
    }

    /**
     * The providers people can sign in with on this installation.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function configured(): array
    {
        return array_values(array_map(
            fn (self $provider): array => ['value' => $provider->value, 'label' => $provider->label()],
            array_filter(self::cases(), fn (self $provider): bool => $provider->isConfigured()),
        ));
    }
}
