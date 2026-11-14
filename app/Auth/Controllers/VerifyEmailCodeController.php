<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\FormRequests\VerifyEmailCodeFormRequest;
use Illuminate\Http\RedirectResponse;

final class VerifyEmailCodeController
{
    public function __invoke(VerifyEmailCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
