<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\Actions\FindPayment;
use App\Payment\Payment;
use App\Payment\PaymentFilters;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The payments a list shows, with its filters, as a CSV file.
 */
final class ExportPaymentsController
{
    public function __invoke(Request $request): StreamedResponse
    {
        $payments = FindPayment::allForTeam((string) $request->user()?->current_team_id, PaymentFilters::fromRequest($request));
        $dollars = fn (int $cents): string => number_format($cents / 100, 2, '.', '');

        return response()->streamDownload(function () use ($payments, $dollars): void {
            $file = fopen('php://output', 'w');

            if ($file === false) {
                return;
            }

            fputcsv($file, ['Number', 'Paid on', 'Customer', 'Amount', 'Currency', 'Applied to invoices', 'Not applied', 'Method', 'Reference', 'Notes', 'Recorded at'], escape: '');

            $payments->each(function (Payment $payment) use ($file, $dollars): void {
                $summary = $payment->toSummary();

                fputcsv($file, [
                    $summary['reference_number'],
                    $summary['paid_on'],
                    $summary['customer_name'],
                    $dollars($summary['amount_in_cents']),
                    $summary['currency'],
                    $dollars($summary['allocated_in_cents']),
                    $dollars($summary['unallocated_in_cents']),
                    $summary['method_label'],
                    $summary['reference'],
                    $summary['notes'],
                    $summary['created_at'],
                ], escape: '');
            });

            fclose($file);
        }, 'payments-'.now()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}
