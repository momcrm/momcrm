<?php

declare(strict_types=1);

namespace App\TeamInvitation\Controllers;

use App\TeamInvitation\FormRequests\DeleteTeamInvitationFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteTeamInvitationController
{
    public function __invoke(DeleteTeamInvitationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
