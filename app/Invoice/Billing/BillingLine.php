<?php

declare(strict_types=1);

namespace App\Invoice\Billing;

use App\Charge\Charge;
use App\Service\Service;
use App\Service\ServiceBillingPeriod;

/**
 * One line a customer is due to be billed: a period of a service, or a
 * one-off charge. Its key names it the same way each time it is worked out,
 * so a line picked from a preview can be found again when the invoice is
 * made, and priced then rather than trusted from the page.
 */
final readonly class BillingLine
{
    public function __construct(
        public string $key,
        public string $description,
        public int $quantity,
        public int $unitPriceInCents,
        public ?string $serviceId = null,
        public ?string $chargeId = null,
        public ?string $rentalStartsOn = null,
        public ?string $rentalEndsOn = null,
    ) {}

    public static function forPeriod(Service $service, ServiceBillingPeriod $period): self
    {
        return new self(
            key: 'service:'.$service->getId()->toString().':'.$period->startsOn->toDateString(),
            description: __(':name · :from – :to', [
                'name' => $service->getName()->toString(),
                'from' => $period->startsOn->format('j M Y'),
                'to' => $period->endsOn->format('j M Y'),
            ]),
            quantity: $service->getQuantity()->toNullableInteger() ?? 1,
            unitPriceInCents: $period->unitPriceInCents,
            serviceId: $service->getId()->toString(),
            rentalStartsOn: $period->startsOn->toDateString(),
            rentalEndsOn: $period->endsOn->toDateString(),
        );
    }

    public static function forCharge(Charge $charge): self
    {
        return new self(
            key: 'charge:'.$charge->getId()->toString(),
            description: $charge->getDescription()->toString(),
            quantity: $charge->getQuantity()->toInteger(),
            unitPriceInCents: $charge->getUnitPriceInCents()->toInteger(),
            chargeId: $charge->getId()->toString(),
        );
    }

    public function totalInCents(): int
    {
        return $this->quantity * $this->unitPriceInCents;
    }

    /**
     * @return array{description: string, quantity: int, unit_price_in_cents: int, service_id: ?string, charge_id: ?string, rental_starts_on: ?string, rental_ends_on: ?string}
     */
    public function toInvoiceItem(): array
    {
        return [
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_in_cents' => $this->unitPriceInCents,
            'service_id' => $this->serviceId,
            'charge_id' => $this->chargeId,
            'rental_starts_on' => $this->rentalStartsOn,
            'rental_ends_on' => $this->rentalEndsOn,
        ];
    }

    /**
     * @return array{key: string, kind: string, description: string, quantity: int, unit_price_in_cents: int, total_in_cents: int}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'kind' => $this->chargeId === null ? 'service' : 'charge',
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price_in_cents' => $this->unitPriceInCents,
            'total_in_cents' => $this->totalInCents(),
        ];
    }
}
