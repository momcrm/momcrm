<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\ReopenTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class ReopenTicketController
{
    public function __invoke(ReopenTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
