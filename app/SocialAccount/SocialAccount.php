<?php

declare(strict_types=1);

namespace App\SocialAccount;

use App\SocialAccount\Factories\SocialAccountFactory;
use App\SocialAccount\Properties\CreatedAt;
use App\SocialAccount\Properties\Email;
use App\SocialAccount\Properties\Id;
use App\SocialAccount\Properties\Provider;
use App\SocialAccount\Properties\ProviderUserId;
use App\SocialAccount\Properties\UpdatedAt;
use App\SocialAccount\Properties\UserId;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<SocialAccountEloquentModel>
 */
final class SocialAccount extends AbstractData
{
    public function __construct(
        public CreatedAt $createdAt,
        public Email $email,
        public Id $id,
        public Provider $provider,
        public ProviderUserId $providerUserId,
        public UpdatedAt $updatedAt,
        public UserId $userId,
    ) {}

    public static function getFactory(): SocialAccountFactory
    {
        return SocialAccountEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getProvider(): Provider
    {
        return $this->provider;
    }

    public function setProvider(mixed $provider): self
    {
        $this->provider = new Provider($provider instanceof AbstractValue ? $provider->toValue() : $provider);

        return $this;
    }

    public function getProviderUserId(): ProviderUserId
    {
        return $this->providerUserId;
    }

    public function setProviderUserId(mixed $providerUserId): self
    {
        $this->providerUserId = new ProviderUserId($providerUserId instanceof AbstractValue ? $providerUserId->toValue() : $providerUserId);

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

    public function setUserId(mixed $userId): self
    {
        $this->userId = new UserId($userId instanceof AbstractValue ? $userId->toValue() : $userId);

        return $this;
    }
}
