<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\Record\FormRequests\StoreRecordFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreRecordController
{
    public function __invoke(StoreRecordFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
