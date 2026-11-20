<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Portal\FormRequests\StorePortalTicketFilesFormRequest;
use Illuminate\Http\RedirectResponse;

final class StorePortalTicketFilesController
{
    public function __invoke(StorePortalTicketFilesFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
