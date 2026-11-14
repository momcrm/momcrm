<?php

declare(strict_types=1);

namespace App\Asset\Controllers;

use App\Asset\FormRequests\DeleteAssetFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteAssetController
{
    public function __invoke(DeleteAssetFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
