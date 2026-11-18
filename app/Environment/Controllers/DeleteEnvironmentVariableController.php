<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\FormRequests\DeleteEnvironmentVariableFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteEnvironmentVariableController
{
    public function __invoke(DeleteEnvironmentVariableFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
