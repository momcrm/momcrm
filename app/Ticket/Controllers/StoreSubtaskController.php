<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\StoreSubtaskFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreSubtaskController
{
    public function __invoke(StoreSubtaskFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
