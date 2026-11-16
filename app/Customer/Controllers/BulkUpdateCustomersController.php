<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\FormRequests\BulkUpdateCustomersFormRequest;
use Illuminate\Http\RedirectResponse;

final class BulkUpdateCustomersController
{
    public function __invoke(BulkUpdateCustomersFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
