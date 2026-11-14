<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\StoreTicketAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreTicketAttachmentController
{
    public function __invoke(StoreTicketAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
