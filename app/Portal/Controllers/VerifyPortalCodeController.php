<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\VerifyPortalCodeFormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class VerifyPortalCodeController
{
    /**
     * @throws ValidationException
     */
    public function __invoke(VerifyPortalCodeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
