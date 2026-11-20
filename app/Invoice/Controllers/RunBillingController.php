<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\RunBillingFormRequest;
use Illuminate\Http\RedirectResponse;

final class RunBillingController
{
    public function __invoke(RunBillingFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
