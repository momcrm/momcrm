<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\ChoosePortalContactFormRequest;
use Illuminate\Http\RedirectResponse;

final class ChoosePortalContactController
{
    public function __invoke(ChoosePortalContactFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
