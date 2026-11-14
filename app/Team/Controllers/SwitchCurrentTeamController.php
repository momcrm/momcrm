<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Team\Exceptions\CouldNotSwitchCurrentTeam;
use App\Team\FormRequests\SwitchCurrentTeamFormRequest;
use Illuminate\Http\RedirectResponse;

final class SwitchCurrentTeamController
{
    /**
     * @throws CouldNotSwitchCurrentTeam
     */
    public function __invoke(SwitchCurrentTeamFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
