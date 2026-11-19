<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\SetServiceAutoRenewFormRequest;
use Illuminate\Http\RedirectResponse;

final class SetServiceAutoRenewController
{
    public function __invoke(SetServiceAutoRenewFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
