<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\BillCustomerFormRequest;
use Illuminate\Http\RedirectResponse;

final class BillCustomerController
{
    public function __invoke(BillCustomerFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
