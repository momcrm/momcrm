<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\BulkUpdateTicketsFormRequest;
use Illuminate\Http\RedirectResponse;

final class BulkUpdateTicketsController
{
    public function __invoke(BulkUpdateTicketsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
