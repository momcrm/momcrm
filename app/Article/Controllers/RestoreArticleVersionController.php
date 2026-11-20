<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\RestoreArticleVersionFormRequest;
use Illuminate\Http\RedirectResponse;

final class RestoreArticleVersionController
{
    public function __invoke(RestoreArticleVersionFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
