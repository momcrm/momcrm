<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\UpdateWorkspaceFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateWorkspaceController
{
    public function __invoke(UpdateWorkspaceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
