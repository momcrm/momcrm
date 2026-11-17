<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\Actions\FindCustomObject;
use App\CustomObject\CustomObjectAccess;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The kinds of record the team has defined for itself, for owners and admins
 * to edit.
 */
final class ShowCustomObjectsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        CustomObjectAccess::authorize($request);

        return Inertia::render('settings/objects', [
            'objects' => FindCustomObject::listForTeam(CustomObjectAccess::team($request)->getId()->toString(), withArchived: true)->toSummaries(),
        ]);
    }
}
