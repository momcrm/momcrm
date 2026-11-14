<?php

declare(strict_types=1);

namespace App\TeamUser;

use App\Shared\Abstracts\AbstractUuid;
use App\TeamUser\Properties\CreatedAt;
use App\TeamUser\Properties\Id;
use App\TeamUser\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UpdatedAt;
use App\TeamUser\Properties\UserId;
use BackedEnum;
use Mom\Data\AbstractData;
use Mom\Data\AbstractEnum;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<TeamUserEloquentModel>
 */
final class TeamUser extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Id $id,
        public Role $role,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
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

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role|AbstractEnum|BackedEnum|string $role): self
    {
        if ($role instanceof Role) {
            $this->role = $role;

            return $this;
        }

        if ($role instanceof BackedEnum) {
            $this->role = Role::fromEnum($role);

            return $this;
        }

        if (is_string($role)) {
            $this->role = Role::fromString($role);

            return $this;
        }

        $this->role = Role::fromNullableEnum($role->toNullableEnum());

        return $this;
    }

    public function getTeamId(): TeamId
    {
        return $this->teamId;
    }

    public function setTeamId(TeamId|AbstractUuid|string $teamId): self
    {
        if ($teamId instanceof TeamId) {
            $this->teamId = $teamId;

            return $this;
        }

        if ($teamId instanceof AbstractUuid) {
            $this->teamId = TeamId::fromNullableUuid($teamId->toNullableString());

            return $this;
        }

        $this->teamId = TeamId::fromNullableUuid($teamId);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }

    public function setUserId(UserId|AbstractUuid|string $userId): self
    {
        if ($userId instanceof UserId) {
            $this->userId = $userId;

            return $this;
        }

        if ($userId instanceof AbstractUuid) {
            $this->userId = UserId::fromNullableUuid($userId->toNullableString());

            return $this;
        }

        $this->userId = UserId::fromNullableUuid($userId);

        return $this;
    }
}
