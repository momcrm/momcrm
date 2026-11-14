<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Project\FormRequests\RestoreProjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreProjectController
{
    public function __invoke(RestoreProjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
