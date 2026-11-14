<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\FormRequests\StoreContactFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreContactController
{
    public function __invoke(StoreContactFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
