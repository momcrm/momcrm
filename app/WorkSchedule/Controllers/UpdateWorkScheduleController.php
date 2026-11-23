<?php

declare(strict_types=1);

namespace App\WorkSchedule\Controllers;

use App\WorkSchedule\FormRequests\UpdateWorkScheduleFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateWorkScheduleController
{
    public function __invoke(UpdateWorkScheduleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
