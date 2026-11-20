<?php

declare(strict_types=1);

namespace App\Product\Controllers;

use App\Product\FormRequests\UpdateProductFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateProductController
{
    public function __invoke(UpdateProductFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
