<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\Asset\FormRequests\UpdateAssetFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateAssetController
{
    public function __invoke(UpdateAssetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
