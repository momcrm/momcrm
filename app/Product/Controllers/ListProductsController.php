<?php

declare(strict_types=1);

namespace App\Product\Controllers;

use App\Product\Actions\FindProduct;
use App\Rate\RateAccess;
use App\Shared\CurrencyEnum;
use App\Team\Exceptions\CouldNotViewTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The team's products, archived ones included, for owners and admins to
 * keep. Products are part of the price list, so they share its permission.
 */
final class ListProductsController
{
    /**
     * @throws CouldNotViewTeam
     */
    public function __invoke(Request $request): Response
    {
        RateAccess::authorize($request);

        return Inertia::render('settings/products', [
            'products' => FindProduct::listForTeam(RateAccess::team($request)->getId()->toString(), withArchived: true)->toSummaries(),
            'currencies' => CurrencyEnum::options(),
        ]);
    }
}
