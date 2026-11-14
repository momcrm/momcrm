<?php

declare(strict_types=1);

namespace App\Activity;

use App\Shared\Traits\Enum;

enum ActivityTypeEnum: string
{
    use Enum;

    case EmailVerified = 'email_verified';
    case PasskeyDeleted = 'passkey_deleted';
    case PasskeyRegistered = 'passkey_registered';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case ProfileUpdated = 'profile_updated';
    case RecoveryCodesRegenerated = 'recovery_codes_regenerated';
    case Registered = 'registered';
    case SignedIn = 'signed_in';
    case SignedOut = 'signed_out';
    case TeamRenamed = 'team_renamed';
    case TeamSwitched = 'team_switched';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorEnabled = 'two_factor_enabled';

    /**
     * A short, human-readable description for activity lists.
     */
    public function label(): string
    {
        return match ($this) {
            self::EmailVerified => 'Verified email address',
            self::PasskeyDeleted => 'Removed a passkey',
            self::PasskeyRegistered => 'Added a passkey',
            self::PasswordChanged => 'Changed password',
            self::PasswordReset => 'Reset password',
            self::ProfileUpdated => 'Updated profile',
            self::RecoveryCodesRegenerated => 'Regenerated recovery codes',
            self::Registered => 'Created account',
            self::SignedIn => 'Signed in',
            self::SignedOut => 'Signed out',
            self::TeamRenamed => 'Renamed the team',
            self::TeamSwitched => 'Switched to this team',
            self::TwoFactorDisabled => 'Disabled two-factor authentication',
            self::TwoFactorEnabled => 'Enabled two-factor authentication',
        };
    }
}
