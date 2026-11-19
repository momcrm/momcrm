<?php

declare(strict_types=1);

namespace App\Rate\Controllers;

use App\Rate\FormRequests\StoreRateFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreRateController
{
    public function __invoke(StoreRateFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
