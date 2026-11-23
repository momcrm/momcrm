<?php

declare(strict_types=1);

namespace App\Holiday\Controllers;

use App\Holiday\FormRequests\StoreHolidayFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreHolidayController
{
    public function __invoke(StoreHolidayFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
