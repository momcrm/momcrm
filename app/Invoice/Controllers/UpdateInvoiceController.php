<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\UpdateInvoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateInvoiceController
{
    public function __invoke(UpdateInvoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
