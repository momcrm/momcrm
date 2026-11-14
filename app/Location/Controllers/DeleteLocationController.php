<?php

declare(strict_types=1);

namespace App\Location\Controllers;

use App\Location\FormRequests\DeleteLocationFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteLocationController
{
    public function __invoke(DeleteLocationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
