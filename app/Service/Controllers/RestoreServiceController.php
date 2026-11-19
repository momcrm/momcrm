<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\RestoreServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreServiceController
{
    public function __invoke(RestoreServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
