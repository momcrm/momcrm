<?php

declare(strict_types=1);

namespace App\Product\Controllers;

use App\Product\FormRequests\ArchiveProductFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveProductController
{
    public function __invoke(ArchiveProductFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
