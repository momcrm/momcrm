<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Controllers;

use App\PaymentAllocation\FormRequests\DeletePaymentAllocationFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeletePaymentAllocationController
{
    public function __invoke(DeletePaymentAllocationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
