<?php

declare(strict_types=1);

namespace App\Charge\Controllers;

use App\Charge\FormRequests\ChargeServicePeriodFormRequest;
use Illuminate\Http\RedirectResponse;

final class ChargeServicePeriodController
{
    public function __invoke(ChargeServicePeriodFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
