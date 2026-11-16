<?php

declare(strict_types=1);

namespace App\Contract\Controllers;

use App\Contract\FormRequests\DeleteContractFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteContractController
{
    public function __invoke(DeleteContractFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
