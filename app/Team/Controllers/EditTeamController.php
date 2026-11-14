<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Shared\Table;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\TeamInvitation\Actions\FindTeamInvitation;
use App\TeamInvitation\TeamInvitation;
use App\TeamUser\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\User\Properties\Email;
use App\User\Properties\Id;
use App\User\Properties\Name;
use App\User\UserEloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class EditTeamController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        $team = FindTeam::byIdForMember(
            id: $request->user()?->current_team_id,
            userId: (string) $request->user()?->getAuthIdentifier(),
        );

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        $canInvite = Gate::allows('invite', $team);

        return Inertia::render('settings/team', [
            'team' => $team->toSummary(),
            'canUpdateTeam' => Gate::allows('update', $team),
            'canInvite' => $canInvite,
            'members' => UserEloquentModel::query()
                ->join(Table::TeamUser->value, UserId::getDatabaseTableColumnNameWithTable(Table::TeamUser), '=', Id::getDatabaseTableColumnNameWithTable(Table::Users))
                ->where(TeamId::getDatabaseTableColumnNameWithTable(Table::TeamUser), $team->getId()->toString())
                ->orderBy(Name::getDatabaseTableColumnNameWithTable(Table::Users))
                ->get([
                    Id::getDatabaseTableColumnNameWithTable(Table::Users),
                    Name::getDatabaseTableColumnNameWithTable(Table::Users),
                    Email::getDatabaseTableColumnNameWithTable(Table::Users),
                    Role::getDatabaseTableColumnNameWithTable(Table::TeamUser),
                ])
                ->map(fn (UserEloquentModel $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->getAttribute(Role::getDatabaseTableColumnName()),
                ])
                ->all(),
            'invitations' => $canInvite
                ? FindTeamInvitation::pendingForTeam($team->getId()->toString())
                    ->map(fn (TeamInvitation $invitation): array => [
                        'id' => $invitation->getId()->toString(),
                        'email' => $invitation->getEmail()->toString(),
                        'role' => $invitation->getRole()->toString(),
                        'expires_at' => $invitation->getExpiresAt()->toNullableISOString(),
                    ])
                    ->values()
                    ->all()
                : [],
        ]);
    }
}
