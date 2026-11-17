<?php

declare(strict_types=1);

namespace App\CustomObject;

use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\Exceptions\CouldNotFindCustomObject;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Who may define the team's custom objects: owners and admins
 * (TeamPolicy::manageCustomObjects).
 */
final class CustomObjectAccess
{
    /**
     * @throws CouldNotViewTeam
     */
    public static function authorize(Request $request): Response
    {
        return Gate::authorize('manageCustomObjects', self::team($request));
    }

    /**
     * @throws CouldNotViewTeam
     */
    public static function team(Request $request): Team
    {
        $team = FindTeam::byIdForMember(id: $request->user()?->current_team_id, userId: (string) $request->user()?->id);

        if ($team->isNull()) {
            throw CouldNotViewTeam::notFound();
        }

        return $team;
    }

    /**
     * The object named in the route, if it is the team's.
     *
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomObject
     */
    public static function object(Request $request): CustomObject
    {
        $object = FindCustomObject::byIdForTeam((string) $request->route('customObject'), self::team($request)->getId()->toString());

        if ($object->isNull()) {
            throw CouldNotFindCustomObject::notFound();
        }

        return $object;
    }
}
