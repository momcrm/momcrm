<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Project\FormRequests\DeleteProjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteProjectController
{
    public function __invoke(DeleteProjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
