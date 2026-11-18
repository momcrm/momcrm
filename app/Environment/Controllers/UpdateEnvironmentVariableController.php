<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\FormRequests\UpdateEnvironmentVariableFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateEnvironmentVariableController
{
    public function __invoke(UpdateEnvironmentVariableFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
