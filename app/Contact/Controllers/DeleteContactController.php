<?php

declare(strict_types=1);

namespace App\Contact\Controllers;

use App\Contact\FormRequests\DeleteContactFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteContactController
{
    public function __invoke(DeleteContactFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
