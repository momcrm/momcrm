<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\StoreCustomObjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCustomObjectController
{
    public function __invoke(StoreCustomObjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
