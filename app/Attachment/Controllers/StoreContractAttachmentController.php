<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\StoreContractAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreContractAttachmentController
{
    public function __invoke(StoreContractAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
