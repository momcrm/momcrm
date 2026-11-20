<?php

declare(strict_types=1);

namespace App\Product\Controllers;

use App\Product\FormRequests\StoreProductFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreProductController
{
    public function __invoke(StoreProductFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
