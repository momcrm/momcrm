<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\BulkUpdateServicesFormRequest;
use Illuminate\Http\RedirectResponse;

final class BulkUpdateServicesController
{
    public function __invoke(BulkUpdateServicesFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
