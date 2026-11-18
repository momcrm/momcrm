<?php

declare(strict_types=1);

namespace App\Environment;

use App\User\Properties\IsPlatformAdmin;
use App\User\UserEloquentModel;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * The `.env` belongs to the whole installation, not to a team, so only
 * platform admins see it, reveal its secrets, change it and apply it. Team
 * owners and admins are not enough.
 */
final class EnvironmentPolicy
{
    use HandlesAuthorization;

    public function manage(UserEloquentModel $user): Response
    {
        if ($user->getAttribute(IsPlatformAdmin::getDatabaseTableColumnName()) === true) {
            return Response::allow();
        }

        return Response::deny('Only platform admins can manage the environment.');
    }
}
