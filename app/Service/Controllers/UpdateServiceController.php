<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\UpdateServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateServiceController
{
    public function __invoke(UpdateServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
