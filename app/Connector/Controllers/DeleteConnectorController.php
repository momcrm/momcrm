<?php

declare(strict_types=1);

namespace App\Connector\Controllers;

use App\Connector\FormRequests\DeleteConnectorFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteConnectorController
{
    public function __invoke(DeleteConnectorFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
