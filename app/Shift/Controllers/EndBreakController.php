<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\EndBreakFormRequest;
use Illuminate\Http\RedirectResponse;

final class EndBreakController
{
    public function __invoke(EndBreakFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
