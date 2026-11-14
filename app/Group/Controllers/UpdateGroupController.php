<?php

declare(strict_types=1);

namespace App\Group\Controllers;

use App\Group\FormRequests\UpdateGroupFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateGroupController
{
    public function __invoke(UpdateGroupFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
