<?php

declare(strict_types=1);

namespace App\Charge\Controllers;

use App\Charge\FormRequests\DeleteChargeFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteChargeController
{
    public function __invoke(DeleteChargeFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
