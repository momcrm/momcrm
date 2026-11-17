<?php

declare(strict_types=1);

namespace App\Comment\Controllers;

use App\Comment\FormRequests\StoreRecordCommentFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreRecordCommentController
{
    public function __invoke(StoreRecordCommentFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
