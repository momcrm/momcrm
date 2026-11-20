<?php

declare(strict_types=1);

namespace App\Invoice\Billing;

use App\Charge\Actions\FindCharge;
use App\Customer\Actions\FindCustomer;
use App\InvoiceItem\Actions\FindInvoiceItem;
use App\Rate\Actions\FindRate;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\CustomerId;
use App\Service\Properties\Name;
use App\Service\Properties\RateId;
use App\Service\Properties\Status;
use App\Service\Service;
use App\Service\ServiceBillingPeriod;
use App\Service\ServiceEloquentModel;
use App\Service\ServiceStatusEnum;
use App\Shared\CurrencyEnum;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;

/**
 * What customers are due to be billed by a day, as Amphora's billing works it
 * out: each period of each active service on a rate that has started by then
 * and isn't on an invoice yet (ServiceBillingPeriod), in the customer's
 * currency, then each one-off charge made by then that isn't on one either.
 * A service whose rate has no price in the customer's currency is left out.
 */
final class CustomerBillingLines
{
    /**
     * @param  list<string>  $customerIds
     * @return array<string, list<BillingLine>> lines keyed by customer id; customers with none are left out
     */
    public static function forCustomers(array $customerIds, CarbonImmutable $day): array
    {
        if ($customerIds === []) {
            return [];
        }

        $services = Service::collect(
            ServiceEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->whereIn(CustomerId::getDatabaseTableColumnName(), $customerIds)
                ->where(Status::getDatabaseTableColumnName(), ServiceStatusEnum::Active->value)
                ->whereNull(ArchivedAt::getDatabaseTableColumnName())
                ->whereNotNull(RateId::getDatabaseTableColumnName())
                ->whereDate(BillingStartsOn::getDatabaseTableColumnName(), '<=', $day->toDateString())
                ->orderBy(Name::getDatabaseTableColumnName())
                ->get()
                ->all(),
            'fromEloquentModel',
        );
        $rates = FindRate::byIds(array_values(array_unique($services->map(fn (Service $service): string => $service->getRateId()->toString())->all())));
        $billedThrough = FindInvoiceItem::billedThroughForServices(array_values($services->map(fn (Service $service): string => $service->getId()->toString())->all()));
        $currencies = FindCustomer::currenciesByIds($customerIds);
        $lines = [];

        foreach ($services as $service) {
            $customerId = $service->getCustomerId()->toString();
            $rate = $rates[$service->getRateId()->toString()] ?? null;
            $price = $rate?->priceIn($currencies[$customerId] ?? CurrencyEnum::default());

            if ($rate === null || $price === null) {
                continue;
            }

            foreach (ServiceBillingPeriod::dueBy($service, $rate, $price, $billedThrough[$service->getId()->toString()] ?? null, $day) as $period) {
                $lines[$customerId][] = BillingLine::forPeriod($service, $period);
            }
        }

        foreach ($customerIds as $customerId) {
            $currency = $currencies[$customerId] ?? CurrencyEnum::default();

            foreach (FindCharge::unbilledForCustomers([$customerId], $currency, $day)[$customerId] ?? [] as $charge) {
                $lines[$customerId][] = BillingLine::forCharge($charge);
            }
        }

        return $lines;
    }

    /**
     * @return list<BillingLine>
     */
    public static function forCustomer(string $customerId, CarbonImmutable $day): array
    {
        return self::forCustomers([$customerId], $day)[$customerId] ?? [];
    }
}
