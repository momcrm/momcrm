<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\StoreShiftFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreShiftController
{
    public function __invoke(StoreShiftFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
