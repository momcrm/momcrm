<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\DeleteServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteServiceController
{
    public function __invoke(DeleteServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
