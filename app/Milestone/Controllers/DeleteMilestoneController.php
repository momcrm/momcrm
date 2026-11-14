<?php

declare(strict_types=1);

namespace App\Milestone\Controllers;

use App\Milestone\FormRequests\DeleteMilestoneFormRequest;
use Illuminate\Http\RedirectResponse;

final class DeleteMilestoneController
{
    public function __invoke(DeleteMilestoneFormRequest $request): RedirectResponse
    {
        return $request->process();
    }
}
