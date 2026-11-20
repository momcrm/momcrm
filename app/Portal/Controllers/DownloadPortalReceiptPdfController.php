<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Payment\PaymentDocument;
use App\Portal\Actions\FindPortalBilling;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * One of the customer's receipts as a PDF, the same one staff send them.
 */
final class DownloadPortalReceiptPdfController
{
    public function __invoke(Request $request, string $payment): Response
    {
        $document = PaymentDocument::for(FindPortalBilling::visibleReceipt($payment));
        $pdf = Pdf::loadView('payments.receipt', ['document' => $document])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($document->fileName())
            : $pdf->stream($document->fileName());
    }
}
