<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\RestoreArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreArticleController
{
    public function __invoke(RestoreArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
