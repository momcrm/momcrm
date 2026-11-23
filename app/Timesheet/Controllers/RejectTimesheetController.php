<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Timesheet\FormRequests\RejectTimesheetFormRequest;
use Illuminate\Http\RedirectResponse;

final class RejectTimesheetController
{
    public function __invoke(RejectTimesheetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
