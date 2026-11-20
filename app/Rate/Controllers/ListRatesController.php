<?php

declare(strict_types=1);

namespace App\Rate\Controllers;

use App\Rate\Actions\FindRate;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\RateAccess;
use App\Shared\CurrencyEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's price list, archived rates included, for owners and admins to edit.
 */
final class ListRatesController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        RateAccess::authorize($request);

        return Inertia::render('settings/rates', [
            'rates' => FindRate::listForTeam(RateAccess::team($request)->getId()->toString(), withArchived: true)->toSummaries(),
            'currencies' => CurrencyEnum::options(),
            'billingPeriods' => BillingPeriodInMonths::ALLOWED,
        ]);
    }
}
