<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\ArchiveCustomObjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveCustomObjectController
{
    public function __invoke(ArchiveCustomObjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
