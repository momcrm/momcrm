<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\StoreCustomerAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCustomerAttachmentController
{
    public function __invoke(StoreCustomerAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
