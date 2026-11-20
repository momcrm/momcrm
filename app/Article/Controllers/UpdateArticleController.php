<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\UpdateArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateArticleController
{
    public function __invoke(UpdateArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
