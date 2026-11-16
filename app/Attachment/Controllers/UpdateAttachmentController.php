<?php

declare(strict_types=1);

namespace App\Attachment\Controllers;

use App\Attachment\FormRequests\UpdateAttachmentFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAttachmentController
{
    public function __invoke(UpdateAttachmentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
