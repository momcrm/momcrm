<?php

declare(strict_types=1);

namespace App\LeaveRequest\Controllers;

use App\LeaveRequest\FormRequests\DeclineLeaveRequestFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeclineLeaveRequestController
{
    public function __invoke(DeclineLeaveRequestFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
