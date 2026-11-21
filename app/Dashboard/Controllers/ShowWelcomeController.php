<?php

declare(strict_types=1);

namespace App\Dashboard\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * The front page, for anyone not signed in to the customer portal.
 */
final class ShowWelcomeController
{
    public function __invoke(): Response
    {
        return Inertia::render('welcome');
    }
}
