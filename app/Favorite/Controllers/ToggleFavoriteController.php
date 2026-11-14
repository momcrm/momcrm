<?php

declare(strict_types=1);

namespace App\Favorite\Controllers;

use App\Favorite\FormRequests\ToggleFavoriteFormRequest;
use Illuminate\Http\RedirectResponse;

final class ToggleFavoriteController
{
    public function __invoke(ToggleFavoriteFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
