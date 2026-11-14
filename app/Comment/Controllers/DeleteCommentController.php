<?php

declare(strict_types=1);

namespace App\Comment\Controllers;

use App\Comment\FormRequests\DeleteCommentFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteCommentController
{
    public function __invoke(DeleteCommentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
