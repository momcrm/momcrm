<?php

declare(strict_types=1);

namespace App\Project\Controllers;

use App\Project\FormRequests\ArchiveProjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveProjectController
{
    public function __invoke(ArchiveProjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
