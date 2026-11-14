<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\FormRequests\CreateAccountFormRequest;
use Illuminate\Http\RedirectResponse;

final class CreateAccountController
{
    public function __invoke(CreateAccountFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
