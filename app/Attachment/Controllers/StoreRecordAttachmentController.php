<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\StoreRecordAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreRecordAttachmentController
{
    public function __invoke(StoreRecordAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
