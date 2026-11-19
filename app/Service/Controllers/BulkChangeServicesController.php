<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\BulkChangeServicesFormRequest;
use Illuminate\Http\RedirectResponse;

final class BulkChangeServicesController
{
    public function __invoke(BulkChangeServicesFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
