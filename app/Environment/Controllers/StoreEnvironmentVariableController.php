<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\FormRequests\StoreEnvironmentVariableFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreEnvironmentVariableController
{
    public function __invoke(StoreEnvironmentVariableFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
