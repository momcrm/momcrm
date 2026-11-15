<?php

declare(strict_types=1);

namespace App\Team;

use App\Shared\Abstracts\AbstractUuid;
use App\Team\Factories\TeamFactory;
use App\Team\Properties\CreatedAt;
use App\Team\Properties\Id;
use App\Team\Properties\IsPersonal;
use App\Team\Properties\Name;
use App\Team\Properties\OwnerId;
use App\Team\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractBoolean;
use Mom\Data\AbstractData;
use Mom\Data\AbstractString;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<TeamEloquentModel>
 */
final class Team extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Id $id,
        public IsPersonal $isPersonal,
        public Name $name,
        public OwnerId $ownerId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): TeamFactory
    {
        return TeamEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): TeamCollection
    {
        if ($items instanceof TeamCollection) {
            return $items;
        }

        return TeamCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The minimal shape the frontend needs to show and switch teams.
     *
     * @return array{id: string, name: string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'name' => $this->getName()->toString(),
        ];
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(Id|AbstractUuid|string|null $id): self
    {
        if ($id instanceof Id) {
            $this->id = $id;

            return $this;
        }

        if ($id instanceof AbstractUuid) {
            $this->id = Id::fromNullableUuid($id->toNullableString());

            return $this;
        }

        $this->id = Id::fromNullableUuid($id);

        return $this;
    }

    public function getIsPersonal(): IsPersonal
    {
        return $this->isPersonal;
    }

    public function setIsPersonal(IsPersonal|AbstractBoolean|bool $isPersonal): self
    {
        if ($isPersonal instanceof IsPersonal) {
            $this->isPersonal = $isPersonal;

            return $this;
        }

        if (is_bool($isPersonal)) {
            $this->isPersonal = IsPersonal::fromBoolean($isPersonal);

            return $this;
        }

        $this->isPersonal = IsPersonal::fromNullableBoolean($isPersonal->toNullableBoolean());

        return $this;
    }

    public function getName(): Name
    {
        return $this->name;
    }

    public function setName(Name|AbstractString|string $name): self
    {
        if ($name instanceof Name) {
            $this->name = $name;

            return $this;
        }

        if (is_string($name)) {
            $this->name = Name::fromString($name);

            return $this;
        }

        $this->name = Name::fromNullableString($name->toNullableString());

        return $this;
    }

    public function getOwnerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function setOwnerId(OwnerId|AbstractUuid|string $ownerId): self
    {
        if ($ownerId instanceof OwnerId) {
            $this->ownerId = $ownerId;

            return $this;
        }

        if ($ownerId instanceof AbstractUuid) {
            $this->ownerId = OwnerId::fromNullableUuid($ownerId->toNullableString());

            return $this;
        }

        $this->ownerId = OwnerId::fromNullableUuid($ownerId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
