<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case Activities = 'activities';
    case Assets = 'assets';
    case AuditLogs = 'audit_logs';
    case Contacts = 'contacts';
    case Customers = 'customers';
    case Locations = 'locations';
    case Passkeys = 'passkeys';
    case Services = 'services';
    case TeamInvitations = 'team_invitations';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Tickets = 'tickets';
    case Users = 'users';
}
