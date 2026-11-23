<?php

declare(strict_types=1);

namespace App\WorkSchedule\Controllers;

use App\WorkSchedule\FormRequests\DeleteWorkScheduleFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteWorkScheduleController
{
    public function __invoke(DeleteWorkScheduleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
