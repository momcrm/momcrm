<?php

declare(strict_types=1);

namespace App\Folder\Controllers;

use App\Folder\FormRequests\StoreFolderFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreFolderController
{
    public function __invoke(StoreFolderFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
