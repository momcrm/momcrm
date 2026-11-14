<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\Location\FormRequests\UpdateLocationFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateLocationController
{
    public function __invoke(UpdateLocationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
