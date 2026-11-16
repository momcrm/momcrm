<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\StoreCustomFieldFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCustomFieldController
{
    public function __invoke(StoreCustomFieldFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
