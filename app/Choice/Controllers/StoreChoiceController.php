<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\StoreChoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreChoiceController
{
    public function __invoke(StoreChoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
