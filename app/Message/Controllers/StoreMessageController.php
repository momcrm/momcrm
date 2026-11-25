<?php

declare(strict_types=1);

namespace App\Message\Controllers;

use App\Message\FormRequests\StoreMessageFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreMessageController
{
    public function __invoke(StoreMessageFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
