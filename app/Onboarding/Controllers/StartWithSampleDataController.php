<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\StartWithSampleDataFormRequest;
use Illuminate\Http\RedirectResponse;

final class StartWithSampleDataController
{
    public function __invoke(StartWithSampleDataFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
