<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\Location\FormRequests\StoreLocationFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreLocationController
{
    public function __invoke(StoreLocationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
