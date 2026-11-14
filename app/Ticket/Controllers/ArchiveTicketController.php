<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\ArchiveTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveTicketController
{
    public function __invoke(ArchiveTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
