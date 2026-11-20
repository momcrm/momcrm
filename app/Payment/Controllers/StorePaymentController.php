<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\StorePaymentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StorePaymentController
{
    public function __invoke(StorePaymentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
