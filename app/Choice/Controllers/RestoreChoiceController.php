<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\RestoreChoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreChoiceController
{
    public function __invoke(RestoreChoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
