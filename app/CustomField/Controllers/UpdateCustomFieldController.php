<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\UpdateCustomFieldFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateCustomFieldController
{
    public function __invoke(UpdateCustomFieldFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
