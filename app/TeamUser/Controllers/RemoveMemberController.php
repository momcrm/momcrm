<?php

declare(strict_types=1);

namespace App\TeamUser\Controllers;

use App\TeamUser\FormRequests\RemoveMemberFormRequest;
use Illuminate\Http\RedirectResponse;

final class RemoveMemberController
{
    public function __invoke(RemoveMemberFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
