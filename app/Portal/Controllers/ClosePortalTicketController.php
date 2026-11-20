<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\ChangePortalTicketStatusFormRequest;
use Illuminate\Http\RedirectResponse;

final class ClosePortalTicketController
{
    public function __invoke(ChangePortalTicketStatusFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
