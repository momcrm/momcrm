<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Controllers;

use App\PaymentAllocation\FormRequests\UpdatePaymentAllocationFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdatePaymentAllocationController
{
    public function __invoke(UpdatePaymentAllocationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
