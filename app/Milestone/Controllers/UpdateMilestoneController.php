<?php

declare(strict_types=1);

namespace App\Milestone\Controllers;

use App\Milestone\FormRequests\UpdateMilestoneFormRequest;
use Illuminate\Http\RedirectResponse;

final class UpdateMilestoneController
{
    public function __invoke(UpdateMilestoneFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
