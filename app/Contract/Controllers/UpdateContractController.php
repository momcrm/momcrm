<?php

declare(strict_types=1);

namespace App\Contract\Controllers;

use App\Contract\FormRequests\UpdateContractFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateContractController
{
    public function __invoke(UpdateContractFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
