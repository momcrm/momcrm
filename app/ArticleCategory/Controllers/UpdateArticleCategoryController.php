<?php

declare(strict_types=1);

namespace App\ArticleCategory\Controllers;

use App\ArticleCategory\FormRequests\UpdateArticleCategoryFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateArticleCategoryController
{
    public function __invoke(UpdateArticleCategoryFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
