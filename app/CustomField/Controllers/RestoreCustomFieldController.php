<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\RestoreCustomFieldFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreCustomFieldController
{
    public function __invoke(RestoreCustomFieldFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
