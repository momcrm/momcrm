<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\InvoiceItem\Actions\FindInvoiceItem;
use App\Service\Exceptions\CouldNotFindService;
use App\Service\ServiceWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The invoice lines that billed a service, newest invoice first.
 */
final class ListServiceInvoicesController
{
    /**
     * @throws CouldNotFindService
     */
    public function __invoke(Request $request, string $service): Response
    {
        $workspace = ServiceWorkspace::open($request, $service);

        return Inertia::render('services/workspace/invoices', [
            ...$workspace->props($request),
            'lines' => FindInvoiceItem::forService($workspace->serviceId(), $workspace->teamId),
        ]);
    }
}
