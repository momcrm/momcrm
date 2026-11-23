<?php

declare(strict_types=1);

namespace App\Holiday\Controllers;

use App\Holiday\FormRequests\DeleteHolidayFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteHolidayController
{
    public function __invoke(DeleteHolidayFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
