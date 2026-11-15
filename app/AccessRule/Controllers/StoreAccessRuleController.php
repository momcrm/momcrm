<?php

declare(strict_types=1);

namespace App\AccessRule\Controllers;

use App\AccessRule\FormRequests\StoreAccessRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreAccessRuleController
{
    public function __invoke(StoreAccessRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
