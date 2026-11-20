<?php

declare(strict_types=1);

namespace App\Service;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Customer\Actions\FindCustomer;
use App\Location\Actions\FindLocation;
use App\Rate\Actions\FindRate;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Rate;
use App\Shared\CurrencyEnum;

/**
 * What the service form offers for one customer: the currency they are billed
 * in, the team's live rates with a price in it (plus the service's own, when
 * it is archived or unpriced), the customer's locations and contacts, and the
 * billing periods.
 */
final class ServiceFormOptions
{
    /**
     * @return array{currency: string, rates: list<array<string, mixed>>, locations: list<array{id: string, name: string}>, contacts: list<array{id: string, name: string}>, statuses: list<array{value: string, label: string}>, billingPeriods: list<int>}
     */
    public static function for(string $customerId, string $teamId, ?Service $service = null): array
    {
        $currency = FindCustomer::currenciesByIds([$customerId])[$customerId] ?? CurrencyEnum::default();
        $allRates = FindRate::listForTeam($teamId, withArchived: true);
        $offered = $allRates
            ->filter(fn (Rate $rate): bool => ($service !== null && $rate->getId()->equalsTo($service->getRateId()->toNullableString()))
                || (! $rate->isArchived() && $rate->priceIn($currency) !== null))
            ->map(fn (Rate $rate): string => $rate->getId()->toString())
            ->all();

        return [
            'currency' => $currency->value,
            'rates' => array_values(array_filter($allRates->toSummaries(), fn (array $rate): bool => in_array($rate['id'], $offered, true))),
            'locations' => array_values(FindLocation::optionsForCustomer($customerId, $teamId)->all()),
            'contacts' => array_values(FindContact::forCustomer($customerId, $teamId)
                ->map(fn (Contact $contact): array => ['id' => $contact->getId()->toString(), 'name' => $contact->getFullName()])
                ->all()),
            'statuses' => ServiceStatusEnum::options(),
            'billingPeriods' => BillingPeriodInMonths::ALLOWED,
        ];
    }
}
