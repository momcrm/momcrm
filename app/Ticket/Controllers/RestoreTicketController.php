<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\RestoreTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreTicketController
{
    public function __invoke(RestoreTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
