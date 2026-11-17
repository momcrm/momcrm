<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\Record\FormRequests\DeleteRecordFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteRecordController
{
    public function __invoke(DeleteRecordFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
