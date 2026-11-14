<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\StoreProjectAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreProjectAttachmentController
{
    public function __invoke(StoreProjectAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
