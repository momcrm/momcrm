<?php

declare(strict_types=1);

namespace App\WorkSchedule\Controllers;

use App\WorkSchedule\FormRequests\StoreWorkScheduleFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreWorkScheduleController
{
    public function __invoke(StoreWorkScheduleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
