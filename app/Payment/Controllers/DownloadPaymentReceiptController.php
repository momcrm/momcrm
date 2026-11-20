<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\Actions\FindPayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\PaymentDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * A payment's receipt as a PDF. Shown in the browser unless ?download=1
 * asks for it to be saved.
 */
final class DownloadPaymentReceiptController
{
    /**
     * @throws CouldNotFindPayment
     */
    public function __invoke(Request $request, string $payment): Response
    {
        $found = FindPayment::byIdForTeam($payment, (string) $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindPayment::notFound();
        }

        $document = PaymentDocument::for($found);
        $pdf = Pdf::loadView('payments.receipt', ['document' => $document])->setPaper('a4');

        return $request->boolean('download')
            ? $pdf->download($document->fileName())
            : $pdf->stream($document->fileName());
    }
}
