<?php

declare(strict_types=1);

namespace App\ArticleCategory\Controllers;

use App\ArticleCategory\FormRequests\DeleteArticleCategoryFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteArticleCategoryController
{
    public function __invoke(DeleteArticleCategoryFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
