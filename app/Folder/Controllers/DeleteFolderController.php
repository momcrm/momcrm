<?php

declare(strict_types=1);

namespace App\Folder\Controllers;

use App\Folder\FormRequests\DeleteFolderFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteFolderController
{
    public function __invoke(DeleteFolderFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
