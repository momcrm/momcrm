<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\ArchiveCustomFieldFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveCustomFieldController
{
    public function __invoke(ArchiveCustomFieldFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
