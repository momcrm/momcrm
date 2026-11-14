<?php

declare(strict_types=1);

namespace App\Milestone\Controllers;

use App\Milestone\FormRequests\StoreMilestoneFormRequest;
use Illuminate\Http\RedirectResponse;

final class StoreMilestoneController
{
    public function __invoke(StoreMilestoneFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
