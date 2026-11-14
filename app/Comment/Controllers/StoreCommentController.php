<?php

declare(strict_types=1);

namespace App\Comment\Controllers;

use App\Comment\FormRequests\StoreCommentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreCommentController
{
    public function __invoke(StoreCommentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
