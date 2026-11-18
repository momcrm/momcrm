<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\FormRequests\StoreMailboxFormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Adds a mailbox that is read and sent through one of the team's connectors.
 */
final class StoreMailboxController
{
    public function __invoke(StoreMailboxFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
