<?php

declare(strict_types=1);

namespace App\Shared;

enum Table: string
{
    case Activities = 'activities';
    case AuditLogs = 'audit_logs';
    case Passkeys = 'passkeys';
    case TeamInvitations = 'team_invitations';
    case TeamUser = 'team_user';
    case Teams = 'teams';
    case Users = 'users';
}
