<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\FormRequests\ApplyEnvironmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class ApplyEnvironmentController
{
    public function __invoke(ApplyEnvironmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
