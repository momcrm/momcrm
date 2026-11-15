<?php

declare(strict_types=1);

namespace App\Asset;

use App\Asset\Factories\AssetFactory;
use App\Asset\Properties\CreatedAt;
use App\Asset\Properties\CustomerId;
use App\Asset\Properties\Description;
use App\Asset\Properties\Id;
use App\Asset\Properties\Labels;
use App\Asset\Properties\LocationId;
use App\Asset\Properties\Name;
use App\Asset\Properties\SerialNumber;
use App\Asset\Properties\Status;
use App\Asset\Properties\TeamId;
use App\Asset\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<AssetEloquentModel>
 */
final class Asset extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Description $description,
        public Id $id,
        public Labels $labels,
        public LocationId $locationId,
        public Name $name,
        public SerialNumber $serialNumber,
        public Status $status,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): AssetFactory
    {
        return AssetEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): AssetCollection
    {
        if ($items instanceof AssetCollection) {
            return $items;
        }

        return AssetCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the asset pages show.
     *
     * @return array{id: string, customer_id: string, name: ?string, serial_number: ?string, location_id: ?string, status: string, description: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'name' => $this->getName()->toNullableString(),
            'serial_number' => $this->getSerialNumber()->toNullableString(),
            'location_id' => $this->getLocationId()->toNullableString(),
            'status' => $this->getStatus()->toString(),
            'description' => $this->getDescription()->toNullableString(),
        ];
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

    public function getSerialNumber(): SerialNumber
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(mixed $serialNumber): self
    {
        $this->serialNumber = new SerialNumber($serialNumber instanceof AbstractValue ? $serialNumber->toValue() : $serialNumber);

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

    public function getLabels(): Labels
    {
        return $this->labels;
    }
}
