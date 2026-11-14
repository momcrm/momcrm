<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Team\FormRequests\UpdateTeamFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateTeamController
{
    public function __invoke(UpdateTeamFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
