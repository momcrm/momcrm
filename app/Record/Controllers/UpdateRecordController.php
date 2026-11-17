<?php

declare(strict_types=1);

namespace App\Record\Controllers;

use App\Record\FormRequests\UpdateRecordFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateRecordController
{
    public function __invoke(UpdateRecordFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
