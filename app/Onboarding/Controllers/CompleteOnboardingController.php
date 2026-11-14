<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Onboarding\FormRequests\CompleteOnboardingFormRequest;
use Illuminate\Http\RedirectResponse;

final class CompleteOnboardingController
{
    public function __invoke(CompleteOnboardingFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
