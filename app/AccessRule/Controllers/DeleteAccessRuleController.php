<?php

declare(strict_types=1);

namespace App\AccessRule\Controllers;

use App\AccessRule\FormRequests\DeleteAccessRuleFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteAccessRuleController
{
    public function __invoke(DeleteAccessRuleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
