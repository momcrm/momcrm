<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\DeleteTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteTicketController
{
    public function __invoke(DeleteTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
