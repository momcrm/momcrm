<?php

declare(strict_types=1);

namespace App\CustomObject\Controllers;

use App\CustomObject\FormRequests\DeleteCustomObjectFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteCustomObjectController
{
    public function __invoke(DeleteCustomObjectFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
