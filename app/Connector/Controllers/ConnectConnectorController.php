<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\FormRequests\ConnectConnectorFormRequest;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sends an owner or admin to an outside service to sign in to the account to connect.
 */
final class ConnectConnectorController
{
    public function __invoke(ConnectConnectorFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
