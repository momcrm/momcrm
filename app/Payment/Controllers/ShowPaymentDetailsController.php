<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Payment\Actions\FindPayment;
use App\Payment\Exceptions\CouldNotFindPayment;
use App\Payment\PaymentDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A payment as the payment dialog shows it, fetched when the dialog opens
 * over a list.
 */
final class ShowPaymentDetailsController
{
    /**
     * @throws CouldNotFindPayment
     */
    public function __invoke(Request $request, string $payment): JsonResponse
    {
        $found = FindPayment::byIdForTeam($payment, (string) $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindPayment::notFound();
        }

        return response()->json(PaymentDetails::for($found, $request->user()));
    }
}
