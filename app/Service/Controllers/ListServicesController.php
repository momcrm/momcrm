<?php

declare(strict_types=1);

namespace App\Service\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Rate\Actions\FindRate;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Service\Actions\FindService;
use App\Service\Service;
use App\Service\ServiceCollection;
use App\Service\ServiceLifecycleEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every customer's services in one list, as Amphora's company services
 * table: search, filter by status, rate, customer and auto-renew, sort, and
 * pick several for a bulk change. Archived services show only when asked for.
 */
final class ListServicesController
{
    public function __invoke(Request $request): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $search = $request->string('search')->trim()->toString() ?: null;
        $lifecycle = ServiceLifecycleEnum::tryFrom($request->string('status')->toString());
        $rateId = Str::isUuid($request->string('rate')->toString()) ? $request->string('rate')->toString() : null;
        $customerId = Str::isUuid($request->string('customer')->toString()) ? $request->string('customer')->toString() : null;
        $autoRenew = match ($request->string('auto_renew')->toString()) {
            '1' => true,
            '0' => false,
            default => null,
        };
        $sort = array_key_exists($request->string('sort')->toString(), FindService::SORTS) ? $request->string('sort')->toString() : 'name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';

        $services = FindService::paginateForTeam($teamId, $search, $lifecycle, $rateId, $customerId, $autoRenew, $sort, $direction);
        $rows = ServiceCollection::make($services->items())->toListItems();

        return Inertia::render('services/index', [
            'services' => $services->through(fn (Service $service, int $index): array => $rows[$index]),
            'filters' => [
                'search' => $search,
                'status' => $lifecycle?->value,
                'rate' => $rateId,
                'customer' => $customerId,
                'auto_renew' => $autoRenew === null ? null : ($autoRenew ? '1' : '0'),
                'sort' => $sort,
                'direction' => $direction,
            ],
            'statuses' => ServiceLifecycleEnum::options(),
            'rates' => FindRate::listForTeam($teamId, withArchived: true)->toSummaries(),
            'customers' => FindCustomer::optionsForTeam($teamId),
            'billingPeriods' => BillingPeriodInMonths::ALLOWED,
        ]);
    }
}
