<?php

declare(strict_types=1);

namespace App\TeamInvitation\Actions;

use App\Team\Scopes\CurrentTeamScope;
use App\TeamInvitation\Properties\AcceptedAt;
use App\TeamInvitation\Properties\Email;
use App\TeamInvitation\Properties\ExpiresAt;
use App\TeamInvitation\Properties\Id;
use App\TeamInvitation\Properties\TeamId;
use App\TeamInvitation\Properties\TokenHash;
use App\TeamInvitation\TeamInvitation;
use App\TeamInvitation\TeamInvitationCollection;
use App\TeamInvitation\TeamInvitationEloquentModel;

final class FindTeamInvitation
{
    /**
     * Finds an invitation from the token in its link, whichever team it is for.
     */
    public static function byToken(string $token): TeamInvitation
    {
        $invitation = TeamInvitationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TokenHash::getDatabaseTableColumnName(), self::hashToken($token))
            ->first();

        return TeamInvitation::fromEloquentModel($invitation);
    }

    public static function byIdForTeam(string $id, string $teamId): TeamInvitation
    {
        $invitation = TeamInvitationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(Id::getDatabaseTableColumnName(), $id)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->first();

        return TeamInvitation::fromEloquentModel($invitation);
    }

    public static function byEmailForTeam(string $email, string $teamId): TeamInvitation
    {
        $invitation = TeamInvitationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(Email::getDatabaseTableColumnName(), $email)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->first();

        return TeamInvitation::fromEloquentModel($invitation);
    }

    /**
     * Invitations that have not been accepted and have not expired, newest first.
     */
    public static function pendingForTeam(string $teamId): TeamInvitationCollection
    {
        $invitations = TeamInvitationEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->where(TeamId::getDatabaseTableColumnName(), $teamId)
            ->whereNull(AcceptedAt::getDatabaseTableColumnName())
            ->where(ExpiresAt::getDatabaseTableColumnName(), '>', now())
            ->latest()
            ->get();

        return TeamInvitation::collect($invitations->all(), 'fromEloquentModel');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
