<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Timesheet\FormRequests\ApproveTimesheetFormRequest;
use Illuminate\Http\RedirectResponse;

final class ApproveTimesheetController
{
    public function __invoke(ApproveTimesheetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
