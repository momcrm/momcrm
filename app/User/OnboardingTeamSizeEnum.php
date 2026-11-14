<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Traits\Enum;

/**
 * The team size a new user told us during onboarding.
 */
enum OnboardingTeamSizeEnum: string
{
    use Enum;

    case Large = '51+';
    case Medium = '11-50';
    case Small = '2-10';
    case Solo = '1';

    public function label(): string
    {
        return match ($this) {
            self::Large => '51+',
            self::Medium => '11–50',
            self::Small => '2–10',
            self::Solo => 'Just me',
        };
    }
}
