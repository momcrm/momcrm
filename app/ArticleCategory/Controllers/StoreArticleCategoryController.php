<?php

declare(strict_types=1);

namespace App\ArticleCategory\Controllers;

use App\ArticleCategory\FormRequests\StoreArticleCategoryFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreArticleCategoryController
{
    public function __invoke(StoreArticleCategoryFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
