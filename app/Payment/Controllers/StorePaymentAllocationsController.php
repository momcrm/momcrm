<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\StorePaymentAllocationsFormRequest;
use Illuminate\Http\RedirectResponse;

final class StorePaymentAllocationsController
{
    public function __invoke(StorePaymentAllocationsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
