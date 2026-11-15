<?php

declare(strict_types=1);

namespace App\Location;

use App\Location\Factories\LocationFactory;
use App\Location\Properties\Country;
use App\Location\Properties\CreatedAt;
use App\Location\Properties\CustomerId;
use App\Location\Properties\Id;
use App\Location\Properties\IsPrimary;
use App\Location\Properties\Name;
use App\Location\Properties\Postcode;
use App\Location\Properties\State;
use App\Location\Properties\StreetOne;
use App\Location\Properties\StreetTwo;
use App\Location\Properties\Suburb;
use App\Location\Properties\TeamId;
use App\Location\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<LocationEloquentModel>
 */
final class Location extends AbstractData
{
    public function __construct(
        public Country $country,
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Id $id,
        public IsPrimary $isPrimary,
        public Name $name,
        public Postcode $postcode,
        public State $state,
        public StreetOne $streetOne,
        public StreetTwo $streetTwo,
        public Suburb $suburb,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): LocationFactory
    {
        return LocationEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): LocationCollection
    {
        if ($items instanceof LocationCollection) {
            return $items;
        }

        return LocationCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the location pages show.
     *
     * @return array{id: string, customer_id: string, name: ?string, street_one: ?string, street_two: ?string, suburb: ?string, state: ?string, postcode: ?string, country: ?string, is_primary: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'name' => $this->getName()->toNullableString(),
            'street_one' => $this->getStreetOne()->toNullableString(),
            'street_two' => $this->getStreetTwo()->toNullableString(),
            'suburb' => $this->getSuburb()->toNullableString(),
            'state' => $this->getState()->toNullableString(),
            'postcode' => $this->getPostcode()->toNullableString(),
            'country' => $this->getCountry()->toNullableString(),
            'is_primary' => $this->getIsPrimary()->toBoolean(),
        ];
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function setCountry(mixed $country): self
    {
        $this->country = new Country($country instanceof AbstractValue ? $country->toValue() : $country);

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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getIsPrimary(): IsPrimary
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(mixed $isPrimary): self
    {
        $this->isPrimary = new IsPrimary($isPrimary instanceof AbstractValue ? $isPrimary->toValue() : $isPrimary);

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

    public function getPostcode(): Postcode
    {
        return $this->postcode;
    }

    public function setPostcode(mixed $postcode): self
    {
        $this->postcode = new Postcode($postcode instanceof AbstractValue ? $postcode->toValue() : $postcode);

        return $this;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(mixed $state): self
    {
        $this->state = new State($state instanceof AbstractValue ? $state->toValue() : $state);

        return $this;
    }

    public function getStreetOne(): StreetOne
    {
        return $this->streetOne;
    }

    public function setStreetOne(mixed $streetOne): self
    {
        $this->streetOne = new StreetOne($streetOne instanceof AbstractValue ? $streetOne->toValue() : $streetOne);

        return $this;
    }

    public function getStreetTwo(): StreetTwo
    {
        return $this->streetTwo;
    }

    public function setStreetTwo(mixed $streetTwo): self
    {
        $this->streetTwo = new StreetTwo($streetTwo instanceof AbstractValue ? $streetTwo->toValue() : $streetTwo);

        return $this;
    }

    public function getSuburb(): Suburb
    {
        return $this->suburb;
    }

    public function setSuburb(mixed $suburb): self
    {
        $this->suburb = new Suburb($suburb instanceof AbstractValue ? $suburb->toValue() : $suburb);

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
