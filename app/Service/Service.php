<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Factories\ServiceFactory;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\CreatedAt;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\Id;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\StartsOn;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Properties\UnitPriceInCents;
use App\Service\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<ServiceEloquentModel>
 */
final class Service extends AbstractData
{
    public function __construct(
        public BillingPeriodInMonths $billingPeriodInMonths,
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public EndsOn $endsOn,
        public Id $id,
        public LocationId $locationId,
        public Name $name,
        public Note $note,
        public Quantity $quantity,
        public StartsOn $startsOn,
        public Status $status,
        public TeamId $teamId,
        public UnitPriceInCents $unitPriceInCents,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ServiceFactory
    {
        return ServiceEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ServiceCollection
    {
        if ($items instanceof ServiceCollection) {
            return $items;
        }

        return ServiceCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the service pages show.
     *
     * @return array{id: string, customer_id: string, name: ?string, status: string, quantity: ?int, unit_price_in_cents: ?int, billing_period_in_months: ?int, starts_on: ?string, ends_on: ?string, location_id: ?string, note: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'name' => $this->getName()->toNullableString(),
            'status' => $this->getStatus()->toString(),
            'quantity' => $this->getQuantity()->toNullableInteger(),
            'unit_price_in_cents' => $this->getUnitPriceInCents()->toNullableInteger(),
            'billing_period_in_months' => $this->getBillingPeriodInMonths()->toNullableInteger(),
            'starts_on' => $this->getStartsOn()->toNullableCarbon()?->toDateString(),
            'ends_on' => $this->getEndsOn()->toNullableCarbon()?->toDateString(),
            'location_id' => $this->getLocationId()->toNullableString(),
            'note' => $this->getNote()->toNullableString(),
        ];
    }

    public function getBillingPeriodInMonths(): BillingPeriodInMonths
    {
        return $this->billingPeriodInMonths;
    }

    public function setBillingPeriodInMonths(mixed $billingPeriodInMonths): self
    {
        $this->billingPeriodInMonths = new BillingPeriodInMonths($billingPeriodInMonths instanceof AbstractValue ? $billingPeriodInMonths->toValue() : $billingPeriodInMonths);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getEndsOn(): EndsOn
    {
        return $this->endsOn;
    }

    public function setEndsOn(mixed $endsOn): self
    {
        $this->endsOn = new EndsOn($endsOn instanceof AbstractValue ? $endsOn->toValue() : $endsOn);

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

    public function getLocationId(): LocationId
    {
        return $this->locationId;
    }

    public function setLocationId(mixed $locationId): self
    {
        $this->locationId = new LocationId($locationId instanceof AbstractValue ? $locationId->toValue() : $locationId);

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

        return $this;
    }

    public function getNote(): Note
    {
        return $this->note;
    }

    public function setNote(mixed $note): self
    {
        $this->note = new Note($note instanceof AbstractValue ? $note->toValue() : $note);

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

    public function getStartsOn(): StartsOn
    {
        return $this->startsOn;
    }

    public function setStartsOn(mixed $startsOn): self
    {
        $this->startsOn = new StartsOn($startsOn instanceof AbstractValue ? $startsOn->toValue() : $startsOn);

        return $this;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(mixed $status): self
    {
        $this->status = new Status($status instanceof AbstractValue ? $status->toValue() : $status);

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
