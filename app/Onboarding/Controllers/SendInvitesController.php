<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\TeamInvitation\FormRequests\StoreTeamInvitationsFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendInvitesController
{
    public function __invoke(StoreTeamInvitationsFormRequest $request): RedirectResponse
    {
        return $request->process(redirectRoute: 'onboarding.start');
    }
}
