<?php

declare(strict_types=1);

namespace App\Team\Controllers;

use App\Team\FormRequests\UpdateBillingDetailsFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateBillingDetailsController
{
    public function __invoke(UpdateBillingDetailsFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
