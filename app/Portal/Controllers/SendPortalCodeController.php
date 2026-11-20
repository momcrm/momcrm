<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\SendPortalCodeFormRequest;
use Illuminate\Http\RedirectResponse;

final class SendPortalCodeController
{
    public function __invoke(SendPortalCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
