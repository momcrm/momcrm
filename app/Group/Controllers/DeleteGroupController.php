<?php

declare(strict_types=1);

namespace App\Group\Controllers;

use App\Group\FormRequests\DeleteGroupFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteGroupController
{
    public function __invoke(DeleteGroupFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
