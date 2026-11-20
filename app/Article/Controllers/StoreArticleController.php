<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\StoreArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreArticleController
{
    public function __invoke(StoreArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
