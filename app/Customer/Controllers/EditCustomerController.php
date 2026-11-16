<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\Customer\Exceptions\CouldNotFindCustomer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EditCustomerController
{
    /**
     * @throws CouldNotFindCustomer
     */
    public function __invoke(Request $request, string $customer): Response
    {
        $found = FindCustomer::byIdForTeam($customer, $request->user()?->current_team_id);

        if ($found->isNull()) {
            throw CouldNotFindCustomer::notFound();
        }

        return Inertia::render('customers/edit', [
            'customer' => $found->toSummary(),
            'statuses' => CustomerStatusEnum::options(),
            'billingTypes' => CustomerBillingTypeEnum::options(),
        ]);
    }
}
