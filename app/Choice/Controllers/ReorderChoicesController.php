<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\ReorderChoicesFormRequest;
use Illuminate\Http\RedirectResponse;

final class ReorderChoicesController
{
    public function __invoke(ReorderChoicesFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
