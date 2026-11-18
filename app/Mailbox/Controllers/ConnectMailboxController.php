<?php

declare(strict_types=1);

namespace App\Mailbox\Controllers;

use App\Mailbox\FormRequests\ConnectMailboxFormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends an owner or admin to Microsoft to sign in to the mailbox to connect.
 */
final class ConnectMailboxController
{
    public function __invoke(ConnectMailboxFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
