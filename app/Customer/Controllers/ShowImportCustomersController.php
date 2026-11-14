<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\ImportCustomersFromCsv;
use Inertia\Inertia;
use Inertia\Response;

final class ShowImportCustomersController
{
    public function __invoke(): Response
    {
        return Inertia::render('customers/import', [
            'maxRows' => ImportCustomersFromCsv::MAX_ROWS,
        ]);
    }
}
