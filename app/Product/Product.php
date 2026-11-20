<?php

declare(strict_types=1);

namespace App\Product;

use App\Product\Factories\ProductFactory;
use App\Product\Properties\ArchivedAt;
use App\Product\Properties\CreatedAt;
use App\Product\Properties\Description;
use App\Product\Properties\Id;
use App\Product\Properties\Name;
use App\Product\Properties\Prices;
use App\Product\Properties\TeamId;
use App\Product\Properties\UpdatedAt;
use App\Rate\RatePrice;
use App\Shared\CurrencyEnum;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * Something the team sells once rather than monthly, such as a laptop or a
 * call-out, priced in one or more currencies. A one-off charge to a customer
 * can start from a product, priced in the customer's currency.
 *
 * @extends AbstractData<ProductEloquentModel>
 */
final class Product extends AbstractData
{
    public function __construct(
        public ArchivedAt $archivedAt,
        public CreatedAt $createdAt,
        public Description $description,
        public Id $id,
        public Name $name,
        public Prices $prices,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): ProductFactory
    {
        return ProductEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ProductCollection
    {
        if ($items instanceof ProductCollection) {
            return $items;
        }

        return ProductCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * @return array{id: string, name: string, description: ?string, prices: list<array{currency: string, price_in_cents: int, cost_in_cents: ?int}>, is_archived: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'prices' => array_map(fn (RatePrice $price): array => $price->toArray(), $this->getPrices()->toPrices()),
            'is_archived' => $this->isArchived(),
        ];
    }

    /**
     * The price in one currency, or null when the product isn't sold in it.
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

    public function getPrices(): Prices
    {
        return $this->prices;
    }

    public function setPrices(mixed $prices): self
    {
        $this->prices = new Prices($prices instanceof AbstractValue ? $prices->toValue() : $prices);

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
