<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\StoreInvoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreInvoiceController
{
    public function __invoke(StoreInvoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
