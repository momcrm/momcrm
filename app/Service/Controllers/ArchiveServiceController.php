<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Service\FormRequests\ArchiveServiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveServiceController
{
    public function __invoke(ArchiveServiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
