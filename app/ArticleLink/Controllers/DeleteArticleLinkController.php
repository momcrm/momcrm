<?php

declare(strict_types=1);

namespace App\ArticleLink\Controllers;

use App\ArticleLink\FormRequests\DeleteArticleLinkFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteArticleLinkController
{
    public function __invoke(DeleteArticleLinkFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
