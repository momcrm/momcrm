<?php

declare(strict_types=1);

namespace App\TeamInvitation\Controllers;

use App\TeamInvitation\FormRequests\AcceptTeamInvitationFormRequest;
use Illuminate\Http\RedirectResponse;

final class AcceptTeamInvitationController
{
    public function __invoke(AcceptTeamInvitationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
