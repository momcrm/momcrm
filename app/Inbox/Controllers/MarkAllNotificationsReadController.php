<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\FormRequests\MarkAllNotificationsReadFormRequest;
use Illuminate\Http\RedirectResponse;

final class MarkAllNotificationsReadController
{
    public function __invoke(MarkAllNotificationsReadFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
