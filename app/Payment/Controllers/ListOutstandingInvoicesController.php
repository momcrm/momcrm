<?php

declare(strict_types=1);

namespace App\Payment\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Actions\FindInvoice;
use App\Payment\PaymentMethodEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What the record payment dialog needs for a customer: the currency they
 * pay in, the ways a payment can be made and their invoices still owing,
 * oldest due first. Fetched when the dialog opens.
 */
final class ListOutstandingInvoicesController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): JsonResponse
    {
        $teamId = (string) $request->user()?->current_team_id;
        $found = FindCustomer::byIdForTeam($customer, $teamId);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return response()->json([
            'customer' => [
                'id' => $found->getId()->toString(),
                'name' => $found->getName()->toString(),
                'currency' => $found->getCurrency()->toEnum()->value,
            ],
            'methods' => PaymentMethodEnum::options(),
            'invoices' => FindInvoice::outstandingForCustomer($found->getId()->toString(), $teamId),
        ]);
    }
}
