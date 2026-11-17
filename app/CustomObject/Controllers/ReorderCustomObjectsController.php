<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\ReorderCustomObjectsFormRequest;
use Illuminate\Http\RedirectResponse;

final class ReorderCustomObjectsController
{
    public function __invoke(ReorderCustomObjectsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
