<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\SendPaymentReceiptFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendPaymentReceiptController
{
    public function __invoke(SendPaymentReceiptFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
