<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\UpdateAboutFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAboutController
{
    public function __invoke(UpdateAboutFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
