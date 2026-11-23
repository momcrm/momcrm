<?php

declare(strict_types=1);

namespace App\LeaveRequest\Controllers;

use App\LeaveRequest\FormRequests\StoreLeaveRequestFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreLeaveRequestController
{
    public function __invoke(StoreLeaveRequestFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
