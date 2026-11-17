<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\UpdateCustomObjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateCustomObjectController
{
    public function __invoke(UpdateCustomObjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
