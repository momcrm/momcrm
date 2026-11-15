<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\UpdateChoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateChoiceController
{
    public function __invoke(UpdateChoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
