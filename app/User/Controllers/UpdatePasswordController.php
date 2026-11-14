<?php

declare(strict_types=1);

namespace App\User\Controllers;

use App\User\FormRequests\UpdatePasswordFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdatePasswordController
{
    public function __invoke(UpdatePasswordFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
