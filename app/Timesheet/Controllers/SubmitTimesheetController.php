<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Timesheet\FormRequests\SubmitTimesheetFormRequest;
use Illuminate\Http\RedirectResponse;

final class SubmitTimesheetController
{
    public function __invoke(SubmitTimesheetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
