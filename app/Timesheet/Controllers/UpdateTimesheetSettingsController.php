<?php

declare(strict_types=1);

namespace App\Timesheet\Controllers;

use App\Timesheet\FormRequests\UpdateTimesheetSettingsFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateTimesheetSettingsController
{
    public function __invoke(UpdateTimesheetSettingsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
