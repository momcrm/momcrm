<?php

declare(strict_types=1);

namespace App\CustomField;

use App\CustomField\Actions\FindCustomField;
use App\CustomField\Exceptions\CouldNotFindCustomField;
use App\Team\Actions\FindTeam;
use App\Team\Exceptions\CouldNotViewTeam;
use App\Team\Team;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Who may edit the team's custom fields: owners and admins
 * (TeamPolicy::manageCustomFields).
 */
final class CustomFieldAccess
{
    /**
     * @throws CouldNotViewTeam
     */
    public static function authorize(Request $request): Response
    {
        return Gate::authorize('manageCustomFields', self::team($request));
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
     * The field named in the route, if it is the team's.
     *
     * @throws CouldNotViewTeam
     * @throws CouldNotFindCustomField
     */
    public static function field(Request $request): CustomField
    {
        $field = FindCustomField::byIdForTeam((string) $request->route('customField'), self::team($request)->getId()->toString());

        if ($field->isNull()) {
            throw CouldNotFindCustomField::notFound();
        }

        return $field;
    }
}
