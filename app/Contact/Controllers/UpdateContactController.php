<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\FormRequests\UpdateContactFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateContactController
{
    public function __invoke(UpdateContactFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
