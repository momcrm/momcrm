<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\MoveTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class MoveTicketController
{
    public function __invoke(MoveTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
