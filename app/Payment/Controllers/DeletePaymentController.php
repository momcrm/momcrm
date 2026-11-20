<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\DeletePaymentFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeletePaymentController
{
    public function __invoke(DeletePaymentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
