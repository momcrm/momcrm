<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\FormRequests\MarkNotificationReadFormRequest;
use Illuminate\Http\RedirectResponse;

final class MarkNotificationReadController
{
    public function __invoke(MarkNotificationReadFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
