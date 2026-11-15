<?php

declare(strict_types=1);

namespace App\Activity\Listeners;

use App\Activity\Actions\RecordActivity;
use App\Activity\ActivityTypeEnum;
use App\Shared\Types;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Features;
use Laravel\Passkeys\Events\PasskeyDeleted;
use Laravel\Passkeys\Events\PasskeyRegistered;

/**
 * Records account-level activity (no team) for authentication and security events.
 */
final class RecordAccountActivity
{
    /**
     * The events this listener handles; registered in AppServiceProvider.
     *
     * @var array<int, class-string>
     */
    public const array EVENTS = [
        Login::class,
        Logout::class,
        PasswordReset::class,
        PasskeyDeleted::class,
        PasskeyRegistered::class,
        RecoveryCodesGenerated::class,
        Registered::class,
        TwoFactorAuthenticationConfirmed::class,
        TwoFactorAuthenticationDisabled::class,
        TwoFactorAuthenticationEnabled::class,
        Verified::class,
    ];

    public function handle(object $event): void
    {
        $type = self::typeFor($event);
        $user = property_exists($event, 'user') ? $event->user : null;

        if ($type === null || ! $user instanceof Authenticatable) {
            return;
        }

        RecordActivity::for(type: $type, userId: Types::string($user->getAuthIdentifier()));
    }

    private static function typeFor(object $event): ?ActivityTypeEnum
    {
        return match (true) {
            $event instanceof Login => ActivityTypeEnum::SignedIn,
            $event instanceof Logout => ActivityTypeEnum::SignedOut,
            $event instanceof PasswordReset => ActivityTypeEnum::PasswordReset,
            $event instanceof PasskeyDeleted => ActivityTypeEnum::PasskeyDeleted,
            $event instanceof PasskeyRegistered => ActivityTypeEnum::PasskeyRegistered,
            $event instanceof RecoveryCodesGenerated => ActivityTypeEnum::RecoveryCodesRegenerated,
            $event instanceof Registered => ActivityTypeEnum::Registered,
            $event instanceof TwoFactorAuthenticationConfirmed => ActivityTypeEnum::TwoFactorEnabled,
            $event instanceof TwoFactorAuthenticationDisabled => ActivityTypeEnum::TwoFactorDisabled,
            // With confirmation required, two-factor is only on once confirmed.
            $event instanceof TwoFactorAuthenticationEnabled => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')
                ? null
                : ActivityTypeEnum::TwoFactorEnabled,
            $event instanceof Verified => ActivityTypeEnum::EmailVerified,
            default => null,
        };
    }
}
