<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use App\Customer\FormRequests\ImportCustomersFormRequest;
use Illuminate\Http\RedirectResponse;

final class StartWithImportController
{
    public function __invoke(ImportCustomersFormRequest $request): RedirectResponse
    {
        return $request->process(redirectRoute: 'customers.index', completesOnboarding: true);
    }
}
