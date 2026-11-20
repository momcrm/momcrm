<?php

declare(strict_types=1);

namespace App\Rate;

use App\Rate\Factories\RateFactory;
use App\Rate\Properties\ArchivedAt;
use App\Rate\Properties\BillingPeriodInMonths;
use App\Rate\Properties\CreatedAt;
use App\Rate\Properties\Description;
use App\Rate\Properties\Id;
use App\Rate\Properties\Name;
use App\Rate\Properties\ParentId;
use App\Rate\Properties\Prices;
use App\Rate\Properties\ProratesFirstMonth;
use App\Rate\Properties\TeamId;
use App\Rate\Properties\UpdatedAt;
use App\Shared\CurrencyEnum;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One entry in the team's price list, such as "Managed IT support" at $95 a
 * month per seat, priced in one or more currencies. A service is priced from
 * its rate, in the currency its customer is billed in. A rate can sit under
 * another, so the price list reads as a tree.
 *
 * @extends AbstractData<RateEloquentModel>
 */
final class Rate extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public BillingPeriodInMonths $billingPeriodInMonths,
        public CreatedAt $createdAt,
        public Description $description,
        public Id $id,
        public Name $name,
        public ParentId $parentId,
        public Prices $prices,
        public ProratesFirstMonth $proratesFirstMonth,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): RateFactory
    {
        return RateEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): RateCollection
    {
        if ($items instanceof RateCollection) {
            return $items;
        }

        return RateCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the rate pages and pickers show.
     *
     * @return array{id: string, parent_id: ?string, name: string, description: ?string, prices: list<array{currency: string, price_in_cents: int, cost_in_cents: ?int}>, billing_period_in_months: int, prorates_first_month: bool, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'parent_id' => $this->getParentId()->toNullableString(),
            'name' => $this->getName()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'prices' => array_map(fn (RatePrice $price): array => $price->toArray(), $this->getPrices()->toPrices()),
            'billing_period_in_months' => $this->getBillingPeriodInMonths()->toInteger(),
            'prorates_first_month' => $this->getProratesFirstMonth()->toBoolean(),
            'is_archived' => $this->isArchived(),
        ];
    }

    /**
     * The price in one currency, or null when the rate isn't sold in it.
     */
    public function priceIn(CurrencyEnum $currency): ?RatePrice
    {
        return $this->getPrices()->in($currency);
    }

    public function isArchived(): bool
    {
        return $this->archivedAt->isNotNull();
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

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(mixed $name): self
    {
        $this->name = new Name($name instanceof AbstractValue ? $name->toValue() : $name);

        return $this;
    }

    public function getParentId(): ParentId
    {
        return $this->parentId;
    }

    public function setParentId(mixed $parentId): self
    {
        $this->parentId = new ParentId($parentId instanceof AbstractValue ? $parentId->toValue() : $parentId);

        return $this;
    }

    public function getPrices(): Prices
    {
        return $this->prices;
    }

    public function setPrices(mixed $prices): self
    {
        $this->prices = new Prices($prices instanceof AbstractValue ? $prices->toValue() : $prices);

        return $this;
    }

    public function getProratesFirstMonth(): ProratesFirstMonth
    {
        return $this->proratesFirstMonth;
    }

    public function setProratesFirstMonth(mixed $proratesFirstMonth): self
    {
        $this->proratesFirstMonth = new ProratesFirstMonth($proratesFirstMonth instanceof AbstractValue ? $proratesFirstMonth->toValue() : $proratesFirstMonth);

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
