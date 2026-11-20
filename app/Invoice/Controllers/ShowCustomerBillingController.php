<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Billing\CustomerBillingLines;
use App\Invoice\PaymentTerms;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What a customer would be billed if they were billed now: their service
 * periods starting by the day asked (today unless given) and their waiting
 * one-off charges, with the due date their terms give. Fetched by the "Bill
 * now" dialog.
 */
final class ShowCustomerBillingController
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

        $day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string('date')->toString()) === 1
            ? CarbonImmutable::parse($request->string('date')->toString())
            : CarbonImmutable::today();

        return response()->json([
            'customer' => [
                'id' => $found->getId()->toString(),
                'name' => $found->getName()->toString(),
                'currency' => $found->getCurrency()->toEnum()->value,
            ],
            'date' => $day->toDateString(),
            'due_on' => PaymentTerms::dueOn($day, $found, FindTeam::byId($teamId))->toDateString(),
            'lines' => array_map(fn (BillingLine $line): array => $line->toArray(), CustomerBillingLines::forCustomer($found->getId()->toString(), $day)),
        ]);
    }
}
