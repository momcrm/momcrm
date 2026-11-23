<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\StartBreakFormRequest;
use Illuminate\Http\RedirectResponse;

final class StartBreakController
{
    public function __invoke(StartBreakFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
