<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\ReorderCustomFieldsFormRequest;
use Illuminate\Http\RedirectResponse;

final class ReorderCustomFieldsController
{
    public function __invoke(ReorderCustomFieldsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
