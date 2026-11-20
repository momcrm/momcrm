<?php

declare(strict_types=1);

namespace App\Article\Controllers;

use App\Article\FormRequests\StoreArticleImageFormRequest;
use Illuminate\Http\JsonResponse;

final class StoreArticleImageController
{
    public function __invoke(StoreArticleImageFormRequest $request): JsonResponse
    {
        return $request->process();
    }
}
