<?php

declare(strict_types=1);

namespace App\TeamUser\Controllers;

use App\TeamUser\FormRequests\UpdateMemberRoleFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateMemberRoleController
{
    public function __invoke(UpdateMemberRoleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
