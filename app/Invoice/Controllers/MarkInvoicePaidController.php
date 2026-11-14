<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\ChangeInvoiceStatusFormRequest;
use App\Invoice\InvoiceStatusEnum;
use Illuminate\Http\RedirectResponse;

final class MarkInvoicePaidController
{
    public function __invoke(ChangeInvoiceStatusFormRequest $request): RedirectResponse
    {
        return $request->process(InvoiceStatusEnum::Paid);
    }
}
