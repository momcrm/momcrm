<?php

declare(strict_types=1);

namespace App\Milestone\Controllers;

use App\Milestone\FormRequests\ToggleMilestoneFormRequest;
use Illuminate\Http\RedirectResponse;

final class ToggleMilestoneController
{
    public function __invoke(ToggleMilestoneFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
