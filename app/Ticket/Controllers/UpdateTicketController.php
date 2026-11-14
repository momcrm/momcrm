<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\UpdateTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateTicketController
{
    public function __invoke(UpdateTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
