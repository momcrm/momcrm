<?php

declare(strict_types=1);

namespace App\Secret\Controllers;

use App\Secret\FormRequests\RevealSecretFormRequest;
use Illuminate\Http\JsonResponse;

final class RevealSecretController
{
    public function __invoke(RevealSecretFormRequest $request): JsonResponse
    {
        return $request->process();
    }
}
