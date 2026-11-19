<?php

declare(strict_types=1);

namespace App\Rate\Controllers;

use App\Rate\FormRequests\UpdateRateFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateRateController
{
    public function __invoke(UpdateRateFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
