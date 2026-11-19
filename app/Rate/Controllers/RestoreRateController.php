<?php

declare(strict_types=1);

namespace App\Rate\Controllers;

use App\Rate\FormRequests\RestoreRateFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreRateController
{
    public function __invoke(RestoreRateFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
