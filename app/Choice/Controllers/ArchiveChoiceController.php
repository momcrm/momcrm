<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\ArchiveChoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveChoiceController
{
    public function __invoke(ArchiveChoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
