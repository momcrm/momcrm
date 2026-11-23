<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\ClockOutFormRequest;
use Illuminate\Http\RedirectResponse;

final class ClockOutController
{
    public function __invoke(ClockOutFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
