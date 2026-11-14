<?php

declare(strict_types=1);

namespace App\Auth\Controllers;

use App\Auth\FormRequests\SendConfirmationCodeFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendConfirmationCodeController
{
    public function __invoke(SendConfirmationCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
