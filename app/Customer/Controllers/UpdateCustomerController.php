<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\UpdateCustomerFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateCustomerController
{
    public function __invoke(UpdateCustomerFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
