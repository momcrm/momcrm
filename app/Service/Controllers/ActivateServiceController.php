<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\ActivateServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class ActivateServiceController
{
    public function __invoke(ActivateServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
