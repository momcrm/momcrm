<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\SendInvoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendInvoiceController
{
    public function __invoke(SendInvoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
