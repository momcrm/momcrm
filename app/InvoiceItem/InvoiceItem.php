<?php

declare(strict_types=1);

namespace App\InvoiceItem;

use App\InvoiceItem\Factories\InvoiceItemFactory;
use App\InvoiceItem\Properties\CreatedAt;
use App\InvoiceItem\Properties\Description;
use App\InvoiceItem\Properties\Id;
use App\InvoiceItem\Properties\InvoiceId;
use App\InvoiceItem\Properties\Position;
use App\InvoiceItem\Properties\Quantity;
use App\InvoiceItem\Properties\RentalEndsOn;
use App\InvoiceItem\Properties\RentalStartsOn;
use App\InvoiceItem\Properties\ServiceId;
use App\InvoiceItem\Properties\TeamId;
use App\InvoiceItem\Properties\UnitPriceInCents;
use App\InvoiceItem\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<InvoiceItemEloquentModel>
 */
final class InvoiceItem extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Description $description,
        public Id $id,
        public InvoiceId $invoiceId,
        public Position $position,
        public Quantity $quantity,
        public RentalEndsOn $rentalEndsOn,
        public RentalStartsOn $rentalStartsOn,
        public ServiceId $serviceId,
        public TeamId $teamId,
        public UnitPriceInCents $unitPriceInCents,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): InvoiceItemFactory
    {
        return InvoiceItemEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): InvoiceItemCollection
    {
        if ($items instanceof InvoiceItemCollection) {
            return $items;
        }

        return InvoiceItemCollection::make(parent::collect($items, $method)->all());
    }

    public function getLineTotalInCents(): int
    {
        return $this->getQuantity()->toInteger() * $this->getUnitPriceInCents()->toInteger();
    }

    /**
     * @return array{id: string, service_id: ?string, description: string, quantity: int, unit_price_in_cents: int, line_total_in_cents: int, rental_starts_on: ?string, rental_ends_on: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'service_id' => $this->getServiceId()->toNullableString(),
            'description' => $this->getDescription()->toString(),
            'quantity' => $this->getQuantity()->toInteger(),
            'unit_price_in_cents' => $this->getUnitPriceInCents()->toInteger(),
            'line_total_in_cents' => $this->getLineTotalInCents(),
            'rental_starts_on' => $this->getRentalStartsOn()->toNullableCarbon()?->toDateString(),
            'rental_ends_on' => $this->getRentalEndsOn()->toNullableCarbon()?->toDateString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(mixed $invoiceId): self
    {
        $this->invoiceId = new InvoiceId($invoiceId instanceof AbstractValue ? $invoiceId->toValue() : $invoiceId);

        return $this;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function setPosition(mixed $position): self
    {
        $this->position = new Position($position instanceof AbstractValue ? $position->toValue() : $position);

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
