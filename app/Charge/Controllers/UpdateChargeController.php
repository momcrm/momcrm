<?php

declare(strict_types=1);

namespace App\Charge\Controllers;

use App\Charge\FormRequests\UpdateChargeFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateChargeController
{
    public function __invoke(UpdateChargeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
