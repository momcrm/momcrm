<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The customer portal's home page.
 */
final class ShowPortalDashboardController
{
    public function __invoke(): Response
    {
        return Inertia::render('portal/dashboard');
    }
}
