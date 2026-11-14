<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\StoreTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreTicketController
{
    public function __invoke(StoreTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
