<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\StorePortalTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class StorePortalTicketController
{
    public function __invoke(StorePortalTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
