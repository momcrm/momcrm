<?php

declare(strict_types=1);

namespace App\Secret\Controllers;

use App\Secret\FormRequests\UpdateSecretFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateSecretController
{
    public function __invoke(UpdateSecretFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
