<?php

declare(strict_types=1);

namespace App\Ticket\Controllers;

use App\Ticket\FormRequests\BulkArchiveTicketsFormRequest;
use Illuminate\Http\RedirectResponse;

final class BulkArchiveTicketsController
{
    public function __invoke(BulkArchiveTicketsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
