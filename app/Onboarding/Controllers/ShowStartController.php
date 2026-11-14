<?php

declare(strict_types=1);

namespace App\Onboarding\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class ShowStartController
{
    public function __invoke(): Response
    {
        return Inertia::render('onboarding/start');
    }
}
