<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\DeleteCustomerFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteCustomerController
{
    public function __invoke(DeleteCustomerFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
