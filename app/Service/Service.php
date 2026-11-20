<?php

declare(strict_types=1);

namespace App\Service;

use App\CustomField\Properties\CustomFieldValues;
use App\Rate\Rate;
use App\Rate\RatePrice;
use App\Service\Factories\ServiceFactory;
use App\Service\Properties\ArchivedAt;
use App\Service\Properties\AutoRenew;
use App\Service\Properties\BillingPeriodInMonths;
use App\Service\Properties\BillingStartsOn;
use App\Service\Properties\ContactId;
use App\Service\Properties\CreatedAt;
use App\Service\Properties\CustomerId;
use App\Service\Properties\EndsOn;
use App\Service\Properties\Id;
use App\Service\Properties\LocationId;
use App\Service\Properties\Name;
use App\Service\Properties\Note;
use App\Service\Properties\Quantity;
use App\Service\Properties\RateId;
use App\Service\Properties\ShortName;
use App\Service\Properties\Status;
use App\Service\Properties\TeamId;
use App\Service\Properties\UpdatedAt;
use App\Shared\CurrencyEnum;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * Something a customer gets from the team and is billed for, priced from a
 * rate: the rate's monthly price in the customer's currency, times the
 * quantity, times the months billed at once.
 *
 * @extends AbstractData<ServiceEloquentModel>
 */
final class Service extends AbstractData
{
    public function __construct(
        public CustomFieldValues $customFields,
        public ArchivedAt $archivedAt,
        public AutoRenew $autoRenew,
        public BillingPeriodInMonths $billingPeriodInMonths,
        public BillingStartsOn $billingStartsOn,
        public ContactId $contactId,
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public EndsOn $endsOn,
        public Id $id,
        public LocationId $locationId,
        public Name $name,
        public Note $note,
        public Quantity $quantity,
        public RateId $rateId,
        public ShortName $shortName,
        public Status $status,
        public TeamId $teamId,
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
     * The fields the service pages show, with what it costs in its customer's
     * currency when its rate is given and has a price in it, and the period
     * it is billed for next when it is billed at all.
     *
     * @return array{id: string, customer_id: string, name: ?string, short_name: ?string, status: string, lifecycle: string, auto_renew: bool, quantity: ?int, currency: string, rate_id: ?string, rate: ?array{id: string, name: string, price_in_cents: ?int, billing_period_in_months: int, is_archived: bool}, billing_period_in_months: ?int, effective_billing_period_in_months: ?int, unit_price_in_cents: ?int, rental_in_cents: ?int, next_invoice: ?array{starts_on: string, ends_on: string, unit_price_in_cents: int, total_in_cents: int}, billing_starts_on: ?string, ends_on: ?string, location_id: ?string, contact_id: ?string, note: ?string, archived_at: ?string, custom_fields: array<string, mixed>}
     */
    public function toSummary(?Rate $rate, CurrencyEnum $currency, ?CarbonImmutable $billedThrough = null): array
    {
        $rate = $rate !== null && $rate->isNotNull() && $rate->getId()->toString() === $this->getRateId()->toNullableString() ? $rate : null;
        $price = $rate?->priceIn($currency);

        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'name' => $this->getName()->toNullableString(),
            'short_name' => $this->getShortName()->toNullableString(),
            'status' => $this->getStatus()->toEnum()->value,
            'lifecycle' => $this->lifecycle()->value,
            'auto_renew' => $this->getAutoRenew()->toBoolean(),
            'quantity' => $this->getQuantity()->toNullableInteger(),
            'currency' => $currency->value,
            'rate_id' => $this->getRateId()->toNullableString(),
            'rate' => $rate === null ? null : [
                'id' => $rate->getId()->toString(),
                'name' => $rate->getName()->toString(),
                'price_in_cents' => $price?->priceInCents,
                'billing_period_in_months' => $rate->getBillingPeriodInMonths()->toInteger(),
                'is_archived' => $rate->isArchived(),
            ],
            'billing_period_in_months' => $this->getBillingPeriodInMonths()->toNullableInteger(),
            'effective_billing_period_in_months' => $rate === null ? $this->getBillingPeriodInMonths()->toNullableInteger() : $this->billingPeriodInMonthsOn($rate),
            'unit_price_in_cents' => $rate === null || $price === null ? null : $this->unitPriceForPeriodInCents($rate, $price),
            'rental_in_cents' => $rate === null || $price === null ? null : $this->rentalInCents($rate, $price),
            'next_invoice' => $rate === null || $price === null || ! $this->isBilled() ? null : $this->nextInvoice($rate, $price, $billedThrough),
            'billing_starts_on' => $this->getBillingStartsOn()->toNullableCarbon()?->toDateString(),
            'ends_on' => $this->getEndsOn()->toNullableCarbon()?->toDateString(),
            'location_id' => $this->getLocationId()->toNullableString(),
            'contact_id' => $this->getContactId()->toNullableString(),
            'note' => $this->getNote()->toNullableString(),
            'archived_at' => $this->getArchivedAt()->toNullableCarbon()?->toIso8601String(),
            'custom_fields' => $this->getCustomFields()->toValues(),
        ];
    }

    /**
     * Active, inactive, or archived, which outranks both.
     */
    public function lifecycle(): ServiceLifecycleEnum
    {
        if ($this->isArchived()) {
            return ServiceLifecycleEnum::Archived;
        }

        return $this->isActive() ? ServiceLifecycleEnum::Active : ServiceLifecycleEnum::Inactive;
    }

    /**
     * Whether the service is billed: on, and not archived.
     */
    public function isBilled(): bool
    {
        return $this->isActive() && ! $this->isArchived();
    }

    /**
     * @return array{starts_on: string, ends_on: string, unit_price_in_cents: int, total_in_cents: int}
     */
    private function nextInvoice(Rate $rate, RatePrice $price, ?CarbonImmutable $billedThrough): array
    {
        $period = ServiceBillingPeriod::next($this, $rate, $price, $billedThrough);

        return [
            ...$period->toArray(),
            'total_in_cents' => $period->unitPriceInCents * ($this->getQuantity()->toNullableInteger() ?? 1),
        ];
    }

    public function isActive(): bool
    {
        return $this->getStatus()->toEnum() === ServiceStatusEnum::Active;
    }

    public function isArchived(): bool
    {
        return $this->getArchivedAt()->isNotNull();
    }

    /**
     * How many months the service is billed for at a time: its own period, or
     * else its rate's.
     */
    public function billingPeriodInMonthsOn(Rate $rate): int
    {
        return $this->getBillingPeriodInMonths()->toNullableInteger()
            ?? $rate->getBillingPeriodInMonths()->toNullableInteger()
            ?? 1;
    }

    /**
     * What one unit costs for a whole billing period: the rate's monthly price
     * times the months billed at once.
     */
    public function unitPriceForPeriodInCents(Rate $rate, RatePrice $price): int
    {
        return $price->priceInCents * $this->billingPeriodInMonthsOn($rate);
    }

    /**
     * What the service costs for a whole billing period, before tax.
     */
    public function rentalInCents(Rate $rate, RatePrice $price): int
    {
        return $this->unitPriceForPeriodInCents($rate, $price) * ($this->getQuantity()->toNullableInteger() ?? 1);
    }

    public function getCustomFields(): CustomFieldValues
    {
        return $this->customFields;
    }

    public function setCustomFields(mixed $customFields): self
    {
        $this->customFields = new CustomFieldValues($customFields instanceof AbstractValue ? $customFields->toValue() : $customFields);

        return $this;
    }

    public function getArchivedAt(): ArchivedAt
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(mixed $archivedAt): self
    {
        $this->archivedAt = new ArchivedAt($archivedAt instanceof AbstractValue ? $archivedAt->toValue() : $archivedAt);

        return $this;
    }

    public function getAutoRenew(): AutoRenew
    {
        return $this->autoRenew;
    }

    public function setAutoRenew(mixed $autoRenew): self
    {
        $this->autoRenew = new AutoRenew($autoRenew instanceof AbstractValue ? $autoRenew->toValue() : $autoRenew);

        return $this;
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

    public function getBillingStartsOn(): BillingStartsOn
    {
        return $this->billingStartsOn;
    }

    public function setBillingStartsOn(mixed $billingStartsOn): self
    {
        $this->billingStartsOn = new BillingStartsOn($billingStartsOn instanceof AbstractValue ? $billingStartsOn->toValue() : $billingStartsOn);

        return $this;
    }

    public function getContactId(): ContactId
    {
        return $this->contactId;
    }

    public function setContactId(mixed $contactId): self
    {
        $this->contactId = new ContactId($contactId instanceof AbstractValue ? $contactId->toValue() : $contactId);

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

    public function getRateId(): RateId
    {
        return $this->rateId;
    }

    public function setRateId(mixed $rateId): self
    {
        $this->rateId = new RateId($rateId instanceof AbstractValue ? $rateId->toValue() : $rateId);

        return $this;
    }

    public function getShortName(): ShortName
    {
        return $this->shortName;
    }

    public function setShortName(mixed $shortName): self
    {
        $this->shortName = new ShortName($shortName instanceof AbstractValue ? $shortName->toValue() : $shortName);

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

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
