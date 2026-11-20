<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\Shared\CurrencyEnum;
use Illuminate\Http\JsonResponse;

/**
 * The choices for the new-customer dialog, fetched when it opens.
 */
final class ListCustomerFormOptionsController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'statuses' => CustomerStatusEnum::options(),
            'billingTypes' => CustomerBillingTypeEnum::options(),
            'currencies' => CurrencyEnum::options(),
        ]);
    }
}
