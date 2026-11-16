<?php

declare(strict_types=1);

namespace App\CustomField\Controllers;

use App\CustomField\FormRequests\DeleteCustomFieldFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteCustomFieldController
{
    public function __invoke(DeleteCustomFieldFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
