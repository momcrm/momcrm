<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\ImportCustomersFormRequest;
use Illuminate\Http\RedirectResponse;

final class ImportCustomersController
{
    public function __invoke(ImportCustomersFormRequest $request): RedirectResponse
    {
        return $request->process(redirectRoute: 'customers.index');
    }
}
