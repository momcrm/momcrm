<?php

declare(strict_types=1);

namespace App\TeamInvitation\Controllers;

use App\TeamInvitation\FormRequests\ResendTeamInvitationFormRequest;
use Illuminate\Http\RedirectResponse;

final class ResendTeamInvitationController
{
    public function __invoke(ResendTeamInvitationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
