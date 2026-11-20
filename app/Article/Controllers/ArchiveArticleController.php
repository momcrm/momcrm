<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\ArchiveArticleFormRequest;
use Illuminate\Http\RedirectResponse;

final class ArchiveArticleController
{
    public function __invoke(ArchiveArticleFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
