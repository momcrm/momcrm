<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\ChangeServiceRateFormRequest;
use Illuminate\Http\RedirectResponse;

final class ChangeServiceRateController
{
    public function __invoke(ChangeServiceRateFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
