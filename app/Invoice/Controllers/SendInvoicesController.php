<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\FormRequests\SendInvoicesFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendInvoicesController
{
    public function __invoke(SendInvoicesFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
