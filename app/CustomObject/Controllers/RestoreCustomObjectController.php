<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\RestoreCustomObjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreCustomObjectController
{
    public function __invoke(RestoreCustomObjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
