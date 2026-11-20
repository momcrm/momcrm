<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\SendCustomerStatementFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendCustomerStatementController
{
    public function __invoke(SendCustomerStatementFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
