<?php

declare(strict_types=1);

namespace App\Service;

use App\Contact\Actions\FindContact;
use App\Contact\Contact;
use App\Location\Actions\FindLocation;
use App\Rate\Actions\FindRate;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Rate;

/**
 * What the service form offers for one customer: the team's live rates (plus
 * the service's own, when it is archived), the customer's locations and
 * contacts, and the billing periods.
 */
final class ServiceFormOptions
{
    /**
     * @return array{rates: list<array<string, mixed>>, locations: list<array{id: string, name: string}>, contacts: list<array{id: string, name: string}>, statuses: list<array{value: string, label: string}>, billingPeriods: list<int>}
     */
    public static function for(string $customerId, string $teamId, ?Service $service = null): array
    {
        $rates = FindRate::listForTeam($teamId, withArchived: true)
            ->filter(fn (Rate $rate): bool => ! $rate->isArchived() || ($service !== null && $rate->getId()->equalsTo($service->getRateId()->toNullableString())));

        return [
            'rates' => array_values($rates->map(fn (Rate $rate): array => $rate->toSummary())->all()),
            'locations' => array_values(FindLocation::optionsForCustomer($customerId, $teamId)->all()),
            'contacts' => array_values(FindContact::forCustomer($customerId, $teamId)
                ->map(fn (Contact $contact): array => ['id' => $contact->getId()->toString(), 'name' => $contact->getFullName()])
                ->all()),
            'statuses' => ServiceStatusEnum::options(),
            'billingPeriods' => BillingPeriodInMonths::ALLOWED,
        ];
    }
}
