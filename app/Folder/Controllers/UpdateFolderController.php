<?php

declare(strict_types=1);

namespace App\Folder\Controllers;

use App\Folder\FormRequests\UpdateFolderFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateFolderController
{
    public function __invoke(UpdateFolderFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
