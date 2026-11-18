<?php

declare(strict_types=1);

namespace App\Environment\Controllers;

use App\Environment\FormRequests\RevealEnvironmentVariableFormRequest;
use Illuminate\Http\JsonResponse;

final class RevealEnvironmentVariableController
{
    public function __invoke(RevealEnvironmentVariableFormRequest $request): JsonResponse
    {
        return $request->process();
    }
}
