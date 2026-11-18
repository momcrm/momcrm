<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\FormRequests\SyncMailboxFormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Reads a mailbox's new mail now, without waiting for the next scheduled read.
 */
final class SyncMailboxController
{
    public function __invoke(SyncMailboxFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
