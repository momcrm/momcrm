<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\FormRequests\SendEmailCodeFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendEmailCodeController
{
    public function __invoke(SendEmailCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
