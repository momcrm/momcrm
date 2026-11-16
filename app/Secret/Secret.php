<?php

declare(strict_types=1);

namespace App\Secret;

use App\Secret\Factories\SecretFactory;
use App\Secret\Properties\AssetId;
use App\Secret\Properties\CreatedAt;
use App\Secret\Properties\CustomerId;
use App\Secret\Properties\Description;
use App\Secret\Properties\Id;
use App\Secret\Properties\TeamId;
use App\Secret\Properties\Title;
use App\Secret\Properties\UpdatedAt;
use App\Secret\Properties\Version;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * A password, key or other credential kept for a customer. Its key/value
 * pairs are deliberately not on this object, so it can be listed, logged and
 * sent to pages without giving them away: read them with RevealSecret.
 *
 * @extends AbstractData<SecretEloquentModel>
 */
final class Secret extends AbstractData
{
    public function __construct(
        public AssetId $assetId,
        public CreatedAt $createdAt,
        public CustomerId $customerId,
        public Description $description,
        public Id $id,
        public TeamId $teamId,
        public Title $title,
        public UpdatedAt $updatedAt,
        public Version $version,
    ) {}

    public static function getFactory(): SecretFactory
    {
        return SecretEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): SecretCollection
    {
        if ($items instanceof SecretCollection) {
            return $items;
        }

        return SecretCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the secrets pages show; never the values.
     *
     * @return array{id: string, customer_id: string, asset_id: ?string, title: string, description: ?string, version: int, updated_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'asset_id' => $this->getAssetId()->toNullableString(),
            'title' => $this->getTitle()->toString(),
            'description' => $this->getDescription()->toNullableString(),
            'version' => $this->getVersion()->toInteger(),
            'updated_at' => $this->getUpdatedAt()->toNullableCarbon()?->toIso8601String(),
        ];
    }

    public function getAssetId(): AssetId
    {
        return $this->assetId;
    }

    public function setAssetId(mixed $assetId): self
    {
        $this->assetId = new AssetId($assetId instanceof AbstractValue ? $assetId->toValue() : $assetId);

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

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(mixed $teamId): self
    {
        $this->teamId = new TeamId($teamId instanceof AbstractValue ? $teamId->toValue() : $teamId);

        return $this;
    }

    public function getTitle(): Title
    {
        return $this->title;
    }

    public function setTitle(mixed $title): self
    {
        $this->title = new Title($title instanceof AbstractValue ? $title->toValue() : $title);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
