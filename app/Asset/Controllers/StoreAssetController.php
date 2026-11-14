<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\Asset\FormRequests\StoreAssetFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreAssetController
{
    public function __invoke(StoreAssetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
