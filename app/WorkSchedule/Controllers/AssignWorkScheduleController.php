<?php

declare(strict_types=1);

namespace App\WorkSchedule\Controllers;

use App\WorkSchedule\FormRequests\AssignWorkScheduleFormRequest;
use Illuminate\Http\RedirectResponse;

final class AssignWorkScheduleController
{
    public function __invoke(AssignWorkScheduleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
