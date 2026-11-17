<?php

declare(strict_types=1);

namespace App\Activity;

use App\Shared\Traits\Enum;

enum ActivityTypeEnum: string
{
    use Enum;

    case AttachmentAdded = 'attachment_added';
    case CustomersImported = 'customers_imported';
    case EmailVerified = 'email_verified';
    case InvitationAccepted = 'invitation_accepted';
    case InvitationSent = 'invitation_sent';
    case OnboardingCompleted = 'onboarding_completed';
    case PasskeyDeleted = 'passkey_deleted';
    case PasskeyRegistered = 'passkey_registered';
    case PasswordChanged = 'password_changed';
    case PasswordReset = 'password_reset';
    case ProjectStatusChanged = 'project_status_changed';
    case ProfileUpdated = 'profile_updated';
    case ProjectArchived = 'project_archived';
    case ProjectCreated = 'project_created';
    case ProjectRestored = 'project_restored';
    case RecordCommented = 'record_commented';
    case RecordCreated = 'record_created';
    case RecordUpdated = 'record_updated';
    case RecoveryCodesRegenerated = 'recovery_codes_regenerated';
    case Registered = 'registered';
    case SampleDataAdded = 'sample_data_added';
    case SampleDataRemoved = 'sample_data_removed';
    case SignedIn = 'signed_in';
    case SignedOut = 'signed_out';
    case TeamRenamed = 'team_renamed';
    case TeamSwitched = 'team_switched';
    case TicketArchived = 'ticket_archived';
    case TicketAssigned = 'ticket_assigned';
    case TicketClosed = 'ticket_closed';
    case TicketCommented = 'ticket_commented';
    case TicketOpened = 'ticket_opened';
    case TicketReopened = 'ticket_reopened';
    case TicketRestored = 'ticket_restored';
    case TicketStatusChanged = 'ticket_status_changed';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorEnabled = 'two_factor_enabled';

    /**
     * The kinds of change that are work on tickets, projects and custom
     * objects' records, as shown in
     * the workspace activity feed (sign-ins and account events are not).
     *
     * @return array<int, self>
     */
    public static function work(): array
    {
        return [
            self::TicketOpened, self::TicketClosed, self::TicketReopened, self::TicketStatusChanged,
            self::TicketAssigned, self::TicketCommented, self::TicketArchived, self::TicketRestored,
            self::ProjectCreated, self::ProjectStatusChanged, self::ProjectArchived, self::ProjectRestored,
            self::RecordCreated, self::RecordUpdated, self::RecordCommented,
            self::AttachmentAdded, self::CustomersImported,
        ];
    }

    /**
     * A short, human-readable description for activity lists.
     */
    public function label(): string
    {
        return match ($this) {
            self::AttachmentAdded => 'Attached a file',
            self::CustomersImported => 'Imported customers',
            self::EmailVerified => 'Verified email address',
            self::InvitationAccepted => 'Joined the team',
            self::InvitationSent => 'Invited someone to the team',
            self::OnboardingCompleted => 'Finished setting up MomCRM',
            self::PasskeyDeleted => 'Removed a passkey',
            self::PasskeyRegistered => 'Added a passkey',
            self::PasswordChanged => 'Changed password',
            self::PasswordReset => 'Reset password',
            self::ProjectStatusChanged => 'Changed a project\'s status',
            self::ProfileUpdated => 'Updated profile',
            self::ProjectArchived => 'Archived a project',
            self::ProjectCreated => 'Created a project',
            self::ProjectRestored => 'Restored a project',
            self::RecordCommented => 'Commented on a record',
            self::RecordCreated => 'Added a record',
            self::RecordUpdated => 'Changed a record',
            self::RecoveryCodesRegenerated => 'Regenerated recovery codes',
            self::Registered => 'Created account',
            self::SampleDataAdded => 'Added sample data',
            self::SampleDataRemoved => 'Removed the sample data',
            self::SignedIn => 'Signed in',
            self::SignedOut => 'Signed out',
            self::TeamRenamed => 'Renamed the team',
            self::TeamSwitched => 'Switched to this team',
            self::TicketArchived => 'Archived a ticket',
            self::TicketAssigned => 'Assigned a ticket',
            self::TicketClosed => 'Closed a ticket',
            self::TicketCommented => 'Commented on a ticket',
            self::TicketOpened => 'Opened a ticket',
            self::TicketReopened => 'Reopened a ticket',
            self::TicketRestored => 'Restored a ticket',
            self::TicketStatusChanged => 'Changed a ticket\'s status',
            self::TwoFactorDisabled => 'Disabled two-factor authentication',
            self::TwoFactorEnabled => 'Enabled two-factor authentication',
        };
    }
}
