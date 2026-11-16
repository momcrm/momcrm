<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use Inertia\Inertia;
use Inertia\Response;

final class CreateCustomerController
{
    public function __invoke(): Response
    {
        return Inertia::render('customers/create', [
            'statuses' => CustomerStatusEnum::options(),
            'billingTypes' => CustomerBillingTypeEnum::options(),
        ]);
    }
}
