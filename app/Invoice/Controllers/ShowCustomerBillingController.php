<?php

declare(strict_types=1);

namespace App\Invoice\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Billing\BillingLine;
use App\Invoice\Billing\BillingMonth;
use App\Invoice\Billing\CustomerBillingLines;
use App\Invoice\PaymentTerms;
use App\Shared\Types;
use App\Team\Actions\FindTeam;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What a customer would be billed for a month (this one unless asked), as
 * Amphora's Multiple Service Billing verifies it: their service periods
 * starting in or before it, of the services asked for when given, and their
 * waiting lines, with the due date their terms give if billed today.
 * Fetched by the Billing tab.
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

        $month = BillingMonth::fromString($request->string('month')->toString());
        $services = $request->has('services') ? Types::stringList($request->input('services')) : null;
        $lines = array_values(array_filter(
            CustomerBillingLines::forCustomer($found->getId()->toString(), $month->lastDay()),
            fn (BillingLine $line): bool => $services === null || $line->serviceId === null || $line->chargeId !== null || in_array($line->serviceId, $services, true),
        ));

        return response()->json([
            'customer' => [
                'id' => $found->getId()->toString(),
                'name' => $found->getName()->toString(),
                'currency' => $found->getCurrency()->toEnum()->value,
            ],
            'month' => $month->toString(),
            'due_on' => PaymentTerms::dueOn(CarbonImmutable::today(), $found, FindTeam::byId($teamId))->toDateString(),
            'lines' => array_map(fn (BillingLine $line): array => $line->toArray(), $lines),
        ]);
    }
}
