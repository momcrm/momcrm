<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Project\FormRequests\UpdateProjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateProjectController
{
    public function __invoke(UpdateProjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
