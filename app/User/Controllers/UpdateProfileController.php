<?php

declare(strict_types=1);

namespace App\User\Controllers;

use App\User\FormRequests\UpdateProfileFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateProfileController
{
    public function __invoke(UpdateProfileFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
