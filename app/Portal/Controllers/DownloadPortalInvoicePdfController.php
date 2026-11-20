<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Invoice\InvoiceDocument;
use App\Portal\Actions\FindPortalBilling;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * One of the customer's invoices as a PDF, the same one staff send them.
 */
final class DownloadPortalInvoicePdfController
{
    public function __invoke(Request $request, string $invoice): Response
    {
        $document = InvoiceDocument::for(FindPortalBilling::visibleInvoice($invoice));
        $pdf = Pdf::loadView('invoices.pdf', ['document' => $document])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($document->fileName())
            : $pdf->stream($document->fileName());
    }
}
