<?php

declare(strict_types=1);

namespace App\EmailCode;

use App\EmailCode\Factories\EmailCodeFactory;
use App\EmailCode\Properties\Attempts;
use App\EmailCode\Properties\CodeHash;
use App\EmailCode\Properties\ConsumedAt;
use App\EmailCode\Properties\CreatedAt;
use App\EmailCode\Properties\Email;
use App\EmailCode\Properties\ExpiresAt;
use App\EmailCode\Properties\Id;
use App\EmailCode\Properties\Purpose;
use App\EmailCode\Properties\UpdatedAt;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<EmailCodeEloquentModel>
 */
final class EmailCode extends AbstractData
{
    public function __construct(
        public Attempts $attempts,
        public CodeHash $codeHash,
        public ConsumedAt $consumedAt,
        public CreatedAt $createdAt,
        public Email $email,
        public ExpiresAt $expiresAt,
        public Id $id,
        public Purpose $purpose,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): EmailCodeFactory
    {
        return EmailCodeEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    public function getAttempts(): Attempts
    {
        return $this->attempts;
    }

    public function setAttempts(mixed $attempts): self
    {
        $this->attempts = new Attempts($attempts instanceof AbstractValue ? $attempts->toValue() : $attempts);

        return $this;
    }

    public function getCodeHash(): CodeHash
    {
        return $this->codeHash;
    }

    public function setCodeHash(mixed $codeHash): self
    {
        $this->codeHash = new CodeHash($codeHash instanceof AbstractValue ? $codeHash->toValue() : $codeHash);

        return $this;
    }

    public function getConsumedAt(): ConsumedAt
    {
        return $this->consumedAt;
    }

    public function setConsumedAt(mixed $consumedAt): self
    {
        $this->consumedAt = new ConsumedAt($consumedAt instanceof AbstractValue ? $consumedAt->toValue() : $consumedAt);

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

    public function getPurpose(): Purpose
    {
        return $this->purpose;
    }

    public function setPurpose(mixed $purpose): self
    {
        $this->purpose = new Purpose($purpose instanceof AbstractValue ? $purpose->toValue() : $purpose);

        return $this;
    }

    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }
}
