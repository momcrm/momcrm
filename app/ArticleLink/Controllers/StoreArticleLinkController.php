<?php

declare(strict_types=1);

namespace App\ArticleLink\Controllers;

use App\ArticleLink\FormRequests\StoreArticleLinkFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreArticleLinkController
{
    public function __invoke(StoreArticleLinkFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
