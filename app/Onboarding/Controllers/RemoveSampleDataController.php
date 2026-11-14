<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\RemoveSampleDataFormRequest;
use Illuminate\Http\RedirectResponse;

final class RemoveSampleDataController
{
    public function __invoke(RemoveSampleDataFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
