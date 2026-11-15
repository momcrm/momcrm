<?php

declare(strict_types=1);

namespace App\TeamInvitation;

use App\TeamInvitation\Factories\TeamInvitationFactory;
use App\TeamInvitation\Properties\AcceptedAt;
use App\TeamInvitation\Properties\CreatedAt;
use App\TeamInvitation\Properties\Email;
use App\TeamInvitation\Properties\ExpiresAt;
use App\TeamInvitation\Properties\Id;
use App\TeamInvitation\Properties\InvitedById;
use App\TeamInvitation\Properties\Role;
use App\TeamInvitation\Properties\TeamId;
use App\TeamInvitation\Properties\TokenHash;
use App\TeamInvitation\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<TeamInvitationEloquentModel>
 */
final class TeamInvitation extends AbstractData
{
    public function __construct(
        public AcceptedAt $acceptedAt,
        public CreatedAt $createdAt,
        public Email $email,
        public ExpiresAt $expiresAt,
        public Id $id,
        public InvitedById $invitedById,
        public Role $role,
        public TeamId $teamId,
        public TokenHash $tokenHash,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): TeamInvitationFactory
    {
        return TeamInvitationEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): TeamInvitationCollection
    {
        if ($items instanceof TeamInvitationCollection) {
            return $items;
        }

        return TeamInvitationCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * Whether the invitation can still be accepted: not yet used and not expired.
     */
    public function isPending(): bool
    {
        return $this->isNotNull()
            && $this->getAcceptedAt()->isNull()
            && $this->getExpiresAt()->isNotNull()
            && $this->getExpiresAt()->toCarbon()->isFuture();
    }

    public function isForEmail(string $email): bool
    {
        return mb_strtolower($this->getEmail()->toString()) === mb_strtolower($email);
    }

    public function getAcceptedAt(): AcceptedAt
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(mixed $acceptedAt): self
    {
        $this->acceptedAt = new AcceptedAt($acceptedAt instanceof AbstractValue ? $acceptedAt->toValue() : $acceptedAt);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(mixed $email): self
    {
        $this->email = new Email($email instanceof AbstractValue ? $email->toValue() : $email);

        return $this;
    }

    public function getExpiresAt(): ExpiresAt
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(mixed $expiresAt): self
    {
        $this->expiresAt = new ExpiresAt($expiresAt instanceof AbstractValue ? $expiresAt->toValue() : $expiresAt);

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

    public function getInvitedById(): InvitedById
    {
        return $this->invitedById;
    }

    public function setInvitedById(mixed $invitedById): self
    {
        $this->invitedById = new InvitedById($invitedById instanceof AbstractValue ? $invitedById->toValue() : $invitedById);

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(mixed $role): self
    {
        $this->role = new Role($role instanceof AbstractValue ? $role->toValue() : $role);

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

    public function getTokenHash(): TokenHash
    {
        return $this->tokenHash;
    }

    public function setTokenHash(mixed $tokenHash): self
    {
        $this->tokenHash = new TokenHash($tokenHash instanceof AbstractValue ? $tokenHash->toValue() : $tokenHash);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
