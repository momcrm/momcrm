<?php

declare(strict_types=1);

namespace App\Secret\Controllers;

use App\Secret\FormRequests\DeleteSecretFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteSecretController
{
    public function __invoke(DeleteSecretFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
