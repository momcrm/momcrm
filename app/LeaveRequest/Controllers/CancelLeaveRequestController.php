<?php

declare(strict_types=1);

namespace App\LeaveRequest\Controllers;

use App\LeaveRequest\FormRequests\CancelLeaveRequestFormRequest;
use Illuminate\Http\RedirectResponse;

final class CancelLeaveRequestController
{
    public function __invoke(CancelLeaveRequestFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
