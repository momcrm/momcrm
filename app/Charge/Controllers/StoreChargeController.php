<?php

declare(strict_types=1);

namespace App\Charge\Controllers;

use App\Charge\FormRequests\StoreChargeFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreChargeController
{
    public function __invoke(StoreChargeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
