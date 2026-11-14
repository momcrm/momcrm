<?php

declare(strict_types=1);

namespace App\User;

use App\Shared\Abstracts\AbstractUuid;
use App\User\Factories\UserFactory;
use App\User\Properties\CreatedAt;
use App\User\Properties\CurrentTeamId;
use App\User\Properties\Email;
use App\User\Properties\EmailVerifiedAt;
use App\User\Properties\Id;
use App\User\Properties\Name;
use App\User\Properties\Password;
use App\User\Properties\UpdatedAt;
use DateTimeInterface;
use Mom\Data\AbstractData;
use Mom\Data\AbstractDate;
use Mom\Data\AbstractString;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<UserEloquentModel>
 */
final class User extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public CurrentTeamId $currentTeamId,
        public Email $email,
        public EmailVerifiedAt $emailVerifiedAt,
        public Id $id,
        public Name $name,
        public Password $password,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): UserFactory
    {
        return UserEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCurrentTeamId(): CurrentTeamId
    {
        return $this->currentTeamId;
    }

    public function setCurrentTeamId(CurrentTeamId|AbstractUuid|string|null $currentTeamId): self
    {
        if ($currentTeamId instanceof CurrentTeamId) {
            $this->currentTeamId = $currentTeamId;

            return $this;
        }

        if ($currentTeamId instanceof AbstractUuid) {
            $this->currentTeamId = CurrentTeamId::fromNullableUuid($currentTeamId->toNullableString());

            return $this;
        }

        $this->currentTeamId = CurrentTeamId::fromNullableUuid($currentTeamId);

        return $this;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(Email|AbstractString|string $email): self
    {
        if ($email instanceof Email) {
            $this->email = $email;

            return $this;
        }

        if (is_string($email)) {
            $this->email = Email::fromString($email);

            return $this;
        }

        $this->email = Email::fromNullableString($email->toNullableString());

        return $this;
    }

    public function getEmailVerifiedAt(): EmailVerifiedAt
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(EmailVerifiedAt|AbstractDate|DateTimeInterface|null $emailVerifiedAt): self
    {
        if ($emailVerifiedAt instanceof EmailVerifiedAt) {
            $this->emailVerifiedAt = $emailVerifiedAt;

            return $this;
        }

        if ($emailVerifiedAt instanceof AbstractDate) {
            $this->emailVerifiedAt = EmailVerifiedAt::fromNullableCarbon($emailVerifiedAt->toNullableCarbon());

            return $this;
        }

        $this->emailVerifiedAt = new EmailVerifiedAt($emailVerifiedAt);

        return $this;
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

    public function getPassword(): Password
    {
        return $this->password;
    }

    public function setPassword(Password|AbstractString|string $password): self
    {
        if ($password instanceof Password) {
            $this->password = $password;

            return $this;
        }

        if (is_string($password)) {
            $this->password = Password::fromString($password);

            return $this;
        }

        $this->password = Password::fromNullableString($password->toNullableString());

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
