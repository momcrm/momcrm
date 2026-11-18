<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\FormRequests\DeleteMailboxFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteMailboxController
{
    public function __invoke(DeleteMailboxFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
