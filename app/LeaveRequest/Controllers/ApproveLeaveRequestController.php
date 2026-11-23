<?php

declare(strict_types=1);

namespace App\LeaveRequest\Controllers;

use App\LeaveRequest\FormRequests\ApproveLeaveRequestFormRequest;
use Illuminate\Http\RedirectResponse;

final class ApproveLeaveRequestController
{
    public function __invoke(ApproveLeaveRequestFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
