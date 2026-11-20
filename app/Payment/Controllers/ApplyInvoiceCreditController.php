<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\FormRequests\ApplyInvoiceCreditFormRequest;
use Illuminate\Http\RedirectResponse;

final class ApplyInvoiceCreditController
{
    public function __invoke(ApplyInvoiceCreditFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
