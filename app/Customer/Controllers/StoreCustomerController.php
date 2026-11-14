<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\StoreCustomerFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCustomerController
{
    public function __invoke(StoreCustomerFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
