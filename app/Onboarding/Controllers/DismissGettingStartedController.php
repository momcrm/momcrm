<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\DismissGettingStartedFormRequest;
use Illuminate\Http\RedirectResponse;

final class DismissGettingStartedController
{
    public function __invoke(DismissGettingStartedFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
