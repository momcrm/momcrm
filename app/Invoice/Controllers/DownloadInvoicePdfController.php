<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Invoice\Actions\FindInvoice;
use App\Invoice\Exceptions\CouldNotFindInvoice;
use App\Invoice\InvoiceDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * An invoice as a PDF, to send to the customer or keep. Shown in the browser
 * unless ?download=1 asks for it to be saved.
 */
final class DownloadInvoicePdfController
{
    /**
     * @throws CouldNotFindInvoice
     */
    public function __invoke(Request $request, string $invoice): Response
    {
        $found = FindInvoice::byIdForTeam($invoice, (string) $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindInvoice::notFound();
        }

        $document = InvoiceDocument::for($found);
        $pdf = Pdf::loadView('invoices.pdf', ['document' => $document])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($document->fileName())
            : $pdf->stream($document->fileName());
    }
}
