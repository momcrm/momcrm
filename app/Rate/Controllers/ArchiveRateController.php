<?php

declare(strict_types=1);

namespace App\Rate\Controllers;

use App\Rate\FormRequests\ArchiveRateFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveRateController
{
    public function __invoke(ArchiveRateFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
