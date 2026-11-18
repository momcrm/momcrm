<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\FormRequests\StoreConnectorFormRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Connects an account that is set up by typing in where its servers are.
 */
final class StoreConnectorController
{
    public function __invoke(StoreConnectorFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
