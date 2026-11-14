<?php

declare(strict_types=1);

namespace App\Activity;

use App\Shared\Traits\Enum;

enum ActivityTypeEnum: string
{
    use Enum;

    case CustomersImported = 'customers_imported';
    case EmailVerified = 'email_verified';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationSent = 'invitation_sent';
    case OnboardingCompleted = 'onboarding_completed';
    case PasskeyDeleted = 'passkey_deleted';
    case PasskeyRegistered = 'passkey_registered';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case ProfileUpdated = 'profile_updated';
    case RecoveryCodesRegenerated = 'recovery_codes_regenerated';
    case Registered = 'registered';
    case SampleDataAdded = 'sample_data_added';
    case SampleDataRemoved = 'sample_data_removed';
    case SignedIn = 'signed_in';
    case SignedOut = 'signed_out';
    case TeamRenamed = 'team_renamed';
    case TeamSwitched = 'team_switched';
    case TicketClosed = 'ticket_closed';
    case TicketOpened = 'ticket_opened';
    case TicketReopened = 'ticket_reopened';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorEnabled = 'two_factor_enabled';

    /**
     * A short, human-readable description for activity lists.
     */
    public function label(): string
    {
        return match ($this) {
            self::CustomersImported => 'Imported customers',
            self::EmailVerified => 'Verified email address',
            self::InvitationAccepted => 'Joined the team',
            self::InvitationSent => 'Invited someone to the team',
            self::OnboardingCompleted => 'Finished setting up MomCRM',
            self::PasskeyDeleted => 'Removed a passkey',
            self::PasskeyRegistered => 'Added a passkey',
            self::PasswordChanged => 'Changed password',
            self::PasswordReset => 'Reset password',
            self::ProfileUpdated => 'Updated profile',
            self::RecoveryCodesRegenerated => 'Regenerated recovery codes',
            self::Registered => 'Created account',
            self::SampleDataAdded => 'Added sample data',
            self::SampleDataRemoved => 'Removed the sample data',
            self::SignedIn => 'Signed in',
            self::SignedOut => 'Signed out',
            self::TeamRenamed => 'Renamed the team',
            self::TeamSwitched => 'Switched to this team',
            self::TicketClosed => 'Closed a ticket',
            self::TicketOpened => 'Opened a ticket',
            self::TicketReopened => 'Reopened a ticket',
            self::TwoFactorDisabled => 'Disabled two-factor authentication',
            self::TwoFactorEnabled => 'Enabled two-factor authentication',
        };
    }
}
