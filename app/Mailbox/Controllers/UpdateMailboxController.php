<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\FormRequests\UpdateMailboxFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateMailboxController
{
    public function __invoke(UpdateMailboxFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
