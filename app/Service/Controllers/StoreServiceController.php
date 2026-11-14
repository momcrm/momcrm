<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\StoreServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreServiceController
{
    public function __invoke(StoreServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
