<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\PatchTicketFormRequest;
use Illuminate\Http\RedirectResponse;

final class PatchTicketController
{
    public function __invoke(PatchTicketFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
