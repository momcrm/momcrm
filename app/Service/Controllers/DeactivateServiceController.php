<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\DeactivateServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeactivateServiceController
{
    public function __invoke(DeactivateServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
