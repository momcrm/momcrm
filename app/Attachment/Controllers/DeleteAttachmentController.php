<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\DeleteAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteAttachmentController
{
    public function __invoke(DeleteAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
