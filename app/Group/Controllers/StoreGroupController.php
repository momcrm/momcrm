<?php

declare(strict_types=1);

namespace App\Group\Controllers;

use App\Group\FormRequests\StoreGroupFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreGroupController
{
    public function __invoke(StoreGroupFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
