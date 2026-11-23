<?php

declare(strict_types=1);

namespace App\Shift\Controllers;

use App\Shift\FormRequests\DeleteShiftFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteShiftController
{
    public function __invoke(DeleteShiftFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
