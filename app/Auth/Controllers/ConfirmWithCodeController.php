<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\FormRequests\ConfirmWithCodeFormRequest;
use Illuminate\Http\RedirectResponse;

final class ConfirmWithCodeController
{
    public function __invoke(ConfirmWithCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
