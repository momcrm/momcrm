<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\UpdateShiftFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateShiftController
{
    public function __invoke(UpdateShiftFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
