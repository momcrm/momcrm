<?php

declare(strict_types=1);

namespace App\Charge\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Exceptions\CouldNotFindCustomer;
use App\Invoice\Billing\BillingMonth;
use App\Invoice\Billing\CustomerBillingLines;
use App\Service\Actions\FindService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The period of one service Single Service Billing would bill for a month:
 * its next one starting in or before the month, or why there is none.
 */
final class ShowServicePeriodController
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
        $service = FindService::byIdForTeam($request->string('service')->toString() ?: null, $teamId);

        if ($service->isNull() || $service->getCustomerId()->toString() !== $found->getId()->toString()) {
            return response()->json(['line' => null, 'reason' => __('Choose one of their services.')]);
        }

        $line = CustomerBillingLines::nextForService($service, $found->getCurrency()->toEnum(), $month->lastDay());

        return response()->json([
            'line' => $line?->toArray(),
            'reason' => $line === null ? self::reason($service->getRateId()->isNull()) : null,
        ]);
    }

    private static function reason(bool $hasNoRate): string
    {
        return $hasNoRate
            ? __('This service isn’t on a rate, so it has nothing to bill.')
            : __('This service is already invoiced, or waiting to be, for every period starting by then, or its rate has no price in the customer’s currency.');
    }
}
