<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\ClockInFormRequest;
use Illuminate\Http\RedirectResponse;

final class ClockInController
{
    public function __invoke(ClockInFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
