<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\StorePortalTicketReplyFormRequest;
use Illuminate\Http\RedirectResponse;

final class StorePortalTicketReplyController
{
    public function __invoke(StorePortalTicketReplyFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
