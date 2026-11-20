<?php

declare(strict_types=1);

namespace App\Customer\Controllers;

use App\Customer\Actions\FindCustomer;
use App\Customer\Customer;
use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerStatusEnum;
use App\CustomField\Actions\FindCustomField;
use App\CustomField\CustomFieldQuery;
use App\CustomField\CustomFieldRelationLabels;
use App\CustomField\CustomFieldTargetEnum;
use App\Invoice\Actions\FindInvoice;
use App\Payment\Actions\FindPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

final class ListCustomersController
{
    public function __invoke(Request $request): Response
    {
        $teamId = (string) $request->user()?->current_team_id;
        $search = $request->string('search')->trim()->toString() ?: null;
        $typeId = Str::isUuid($request->string('type')->toString()) ? $request->string('type')->toString() : null;
        $billingType = CustomerBillingTypeEnum::tryFrom($request->string('billing')->toString());
        $status = CustomerStatusEnum::tryFrom($request->string('status')->toString());

        $customFields = CustomFieldQuery::filtersFromRequest($request, FindCustomField::listForTeam($teamId, CustomFieldTargetEnum::Customer));

        $customers = FindCustomer::paginateForTeam($teamId, $search, $typeId, $billingType, $status, customFields: $customFields);
        $labels = CustomFieldRelationLabels::for(
            $teamId,
            CustomFieldTargetEnum::Customer,
            ...array_map(fn (Customer $customer): array => $customer->getCustomFields()->toValues(), $customers->items()),
        );
        $customerIds = $customers->getCollection()
            ->map(fn (Customer $customer): string => $customer->getId()->toString())
            ->all();
        $balances = FindInvoice::balancesForCustomers($teamId, $customerIds);
        $credit = FindPayment::creditForCustomers($teamId, array_values($customerIds));

        return Inertia::render('customers/index', [
            'customers' => $customers->through(fn (Customer $customer): array => [
                ...$customer->toSummary(),
                'custom_field_labels' => CustomFieldRelationLabels::only($labels, $customer->getCustomFields()->toValues()),
                'contacts_count' => $customer->getContactsCount()->toInteger(),
                'services_count' => $customer->getServicesCount()->toInteger(),
                'balance_in_cents' => $balances[$customer->getId()->toString()]['balance_in_cents'] ?? 0,
                'credit_in_cents' => $credit[$customer->getId()->toString()] ?? 0,
                'updated_at' => $customer->getUpdatedAt()->toNullableCarbon()?->toIso8601String(),
            ]),
            'filters' => [
                'search' => $search,
                'type' => $typeId,
                'billing' => $billingType?->value,
                'status' => $status?->value,
                'custom_fields' => $customFields,
            ],
            'billingTypes' => CustomerBillingTypeEnum::options(),
            'statuses' => CustomerStatusEnum::options(),
        ]);
    }
}
