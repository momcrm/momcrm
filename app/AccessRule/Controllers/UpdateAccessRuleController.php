<?php

declare(strict_types=1);

namespace App\AccessRule\Controllers;

use App\AccessRule\FormRequests\UpdateAccessRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAccessRuleController
{
    public function __invoke(UpdateAccessRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
