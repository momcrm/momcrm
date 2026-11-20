<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\ReviewArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class ReviewArticleController
{
    public function __invoke(ReviewArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
