<?php

declare(strict_types=1);

namespace App\Inbox\Controllers;

use App\Inbox\FormRequests\UpdateNotificationEmailsFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateNotificationEmailsController
{
    public function __invoke(UpdateNotificationEmailsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
