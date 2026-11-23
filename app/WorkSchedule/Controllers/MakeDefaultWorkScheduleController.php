<?php

declare(strict_types=1);

namespace App\WorkSchedule\Controllers;

use App\WorkSchedule\FormRequests\MakeDefaultWorkScheduleFormRequest;
use Illuminate\Http\RedirectResponse;

final class MakeDefaultWorkScheduleController
{
    public function __invoke(MakeDefaultWorkScheduleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
