<?php

declare(strict_types=1);

namespace App\Choice\Controllers;

use App\Choice\FormRequests\DeleteChoiceFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteChoiceController
{
    public function __invoke(DeleteChoiceFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
