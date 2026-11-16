<?php

declare(strict_types=1);

namespace App\Contract\Controllers;

use App\Contract\FormRequests\StoreContractFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreContractController
{
    public function __invoke(StoreContractFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
