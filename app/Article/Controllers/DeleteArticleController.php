<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\DeleteArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteArticleController
{
    public function __invoke(DeleteArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
