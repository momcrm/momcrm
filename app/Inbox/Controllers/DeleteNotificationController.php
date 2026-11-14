<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\FormRequests\DeleteNotificationFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteNotificationController
{
    public function __invoke(DeleteNotificationFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
