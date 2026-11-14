<?php

declare(strict_types=1);

namespace App\TeamInvitation\Controllers;

use App\TeamInvitation\FormRequests\StoreTeamInvitationsFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreTeamInvitationController
{
    public function __invoke(StoreTeamInvitationsFormRequest $request): RedirectResponse
    {
        return $request->process(redirectRoute: 'team.edit');
    }
}
