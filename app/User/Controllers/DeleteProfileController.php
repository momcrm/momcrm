<?php

declare(strict_types=1);

namespace App\User\Controllers;

use App\User\FormRequests\DeleteProfileFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteProfileController
{
    public function __invoke(DeleteProfileFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
