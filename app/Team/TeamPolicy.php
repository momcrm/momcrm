<?php

declare(strict_types=1);

namespace App\Team;

use App\User\UserEloquentModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class TeamPolicy
{
    use HandlesAuthorization;

    public function update(UserEloquentModel $user, Team $team): Response
    {
        if ($team->getOwnerId()->toString() === $user->getAuthIdentifier()) {
            return Response::allow();
        }

        return Response::deny('Only the team owner can update the team.');
    }
}
