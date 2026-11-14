<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\CloseTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class CloseTicketController
{
    public function __invoke(CloseTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
