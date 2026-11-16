<?php

declare(strict_types=1);

namespace App\Secret\Controllers;

use App\Secret\FormRequests\StoreSecretFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreSecretController
{
    public function __invoke(StoreSecretFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
