<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Project\FormRequests\StoreProjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreProjectController
{
    public function __invoke(StoreProjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
