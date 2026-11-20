<?php

declare(strict_types=1);

namespace App\Charge;

use App\Charge\Factories\ChargeFactory;
use App\Charge\Properties\ChargedOn;
use App\Charge\Properties\CreatedAt;
use App\Charge\Properties\Currency;
use App\Charge\Properties\CustomerId;
use App\Charge\Properties\Description;
use App\Charge\Properties\Id;
use App\Charge\Properties\ProductId;
use App\Charge\Properties\Quantity;
use App\Charge\Properties\RentalEndsOn;
use App\Charge\Properties\RentalStartsOn;
use App\Charge\Properties\ServiceId;
use App\Charge\Properties\TeamId;
use App\Charge\Properties\UnitPriceInCents;
use App\Charge\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A one-off charge to a customer, such as a laptop or a call-out, or one
 * period of a service billed on its own (Single Service Billing), that waits
 * to go on their next invoice. A waiting service period counts as billed, so
 * the service isn't billed for it twice. It is billed while it is on an invoice that
 * isn't voided (invoice_items.charge_id), so voiding that invoice puts it
 * back in the queue.
 *
 * @extends AbstractData<ChargeEloquentModel>
 */
final class Charge extends AbstractData
{
    public function __construct(
        public ChargedOn $chargedOn,
        public CreatedAt $createdAt,
        public Currency $currency,
        public CustomerId $customerId,
        public Description $description,
        public Id $id,
        public ProductId $productId,
        public Quantity $quantity,
        public RentalEndsOn $rentalEndsOn,
        public RentalStartsOn $rentalStartsOn,
        public ServiceId $serviceId,
        public TeamId $teamId,
        public UnitPriceInCents $unitPriceInCents,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ChargeFactory
    {
        return ChargeEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ChargeCollection
    {
        if ($items instanceof ChargeCollection) {
            return $items;
        }

        return ChargeCollection::make(parent::collect($items, $method)->all());
    }

    public function totalInCents(): int
    {
        return $this->getQuantity()->toInteger() * $this->getUnitPriceInCents()->toInteger();
    }

    /**
     * The charge as an invoice line.
     *
     * @return array{description: string, quantity: int, unit_price_in_cents: int, charge_id: string, service_id: ?string, rental_starts_on: ?string, rental_ends_on: ?string}
     */
    public function toInvoiceItem(): array
    {
        return [
            'description' => $this->getDescription()->toString(),
            'quantity' => $this->getQuantity()->toInteger(),
            'unit_price_in_cents' => $this->getUnitPriceInCents()->toInteger(),
            'charge_id' => $this->getId()->toString(),
            'service_id' => $this->getServiceId()->toNullableString(),
            'rental_starts_on' => $this->getRentalStartsOn()->toNullableCarbon()?->toDateString(),
            'rental_ends_on' => $this->getRentalEndsOn()->toNullableCarbon()?->toDateString(),
        ];
    }

    /**
     * @param  array{id: string, reference: string}|null  $billedOn  the invoice it is on, when billed
     * @return array{id: string, customer_id: string, product_id: ?string, service_id: ?string, rental_starts_on: ?string, rental_ends_on: ?string, description: string, quantity: int, unit_price_in_cents: int, total_in_cents: int, currency: string, charged_on: ?string, invoice: array{id: string, reference: string}|null}
     */
    public function toSummary(?array $billedOn = null): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'product_id' => $this->getProductId()->toNullableString(),
            'service_id' => $this->getServiceId()->toNullableString(),
            'rental_starts_on' => $this->getRentalStartsOn()->toNullableCarbon()?->toDateString(),
            'rental_ends_on' => $this->getRentalEndsOn()->toNullableCarbon()?->toDateString(),
            'description' => $this->getDescription()->toString(),
            'quantity' => $this->getQuantity()->toInteger(),
            'unit_price_in_cents' => $this->getUnitPriceInCents()->toInteger(),
            'total_in_cents' => $this->totalInCents(),
            'currency' => $this->getCurrency()->toEnum()->value,
            'charged_on' => $this->getChargedOn()->toNullableCarbon()?->toDateString(),
            'invoice' => $billedOn,
        ];
    }

    public function getChargedOn(): ChargedOn
    {
        return $this->chargedOn;
    }

    public function setChargedOn(mixed $chargedOn): self
    {
        $this->chargedOn = new ChargedOn($chargedOn instanceof AbstractValue ? $chargedOn->toValue() : $chargedOn);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(mixed $currency): self
    {
        $this->currency = new Currency($currency instanceof AbstractValue ? $currency->toValue() : $currency);

        return $this;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function setCustomerId(mixed $customerId): self
    {
        $this->customerId = new CustomerId($customerId instanceof AbstractValue ? $customerId->toValue() : $customerId);

        return $this;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

        return $this;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function setProductId(mixed $productId): self
    {
        $this->productId = new ProductId($productId instanceof AbstractValue ? $productId->toValue() : $productId);

        return $this;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function setQuantity(mixed $quantity): self
    {
        $this->quantity = new Quantity($quantity instanceof AbstractValue ? $quantity->toValue() : $quantity);

        return $this;
    }

    public function getRentalEndsOn(): RentalEndsOn
    {
        return $this->rentalEndsOn;
    }

    public function setRentalEndsOn(mixed $rentalEndsOn): self
    {
        $this->rentalEndsOn = new RentalEndsOn($rentalEndsOn instanceof AbstractValue ? $rentalEndsOn->toValue() : $rentalEndsOn);

        return $this;
    }

    public function getRentalStartsOn(): RentalStartsOn
    {
        return $this->rentalStartsOn;
    }

    public function setRentalStartsOn(mixed $rentalStartsOn): self
    {
        $this->rentalStartsOn = new RentalStartsOn($rentalStartsOn instanceof AbstractValue ? $rentalStartsOn->toValue() : $rentalStartsOn);

        return $this;
    }

    public function getServiceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function setServiceId(mixed $serviceId): self
    {
        $this->serviceId = new ServiceId($serviceId instanceof AbstractValue ? $serviceId->toValue() : $serviceId);

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getUnitPriceInCents(): UnitPriceInCents
    {
        return $this->unitPriceInCents;
    }

    public function setUnitPriceInCents(mixed $unitPriceInCents): self
    {
        $this->unitPriceInCents = new UnitPriceInCents($unitPriceInCents instanceof AbstractValue ? $unitPriceInCents->toValue() : $unitPriceInCents);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
