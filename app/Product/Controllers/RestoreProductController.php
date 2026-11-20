<?php

declare(strict_types=1);

namespace App\Product\Controllers;

use App\Product\FormRequests\RestoreProductFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreProductController
{
    public function __invoke(RestoreProductFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
