<?php

declare(strict_types=1);

namespace App\Customer;

use App\Customer\Factories\CustomerFactory;
use App\Customer\Properties\Abn;
use App\Customer\Properties\ContactsCount;
use App\Customer\Properties\CreatedAt;
use App\Customer\Properties\Email;
use App\Customer\Properties\Id;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Number;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Status;
use App\Customer\Properties\TeamId;
use App\Customer\Properties\UpdatedAt;
use App\Customer\Properties\Website;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<CustomerEloquentModel>
 */
final class Customer extends AbstractData
{
    public function __construct(
        public Abn $abn,
        public ContactsCount $contactsCount,
        public CreatedAt $createdAt,
        public Email $email,
        public Id $id,
        public IsSample $isSample,
        public Name $name,
        public Notes $notes,
        public Number $number,
        public Phone $phone,
        public Status $status,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public Website $website,
    ) {}

    public static function getFactory(): CustomerFactory
    {
        return CustomerEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * The fields the customer pages show.
     *
     * @return array{id: string, number: int, name: string, abn: ?string, email: ?string, phone: ?string, website: ?string, status: string, notes: ?string, is_sample: bool}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'name' => $this->getName()->toString(),
            'abn' => $this->getAbn()->toNullableString(),
            'email' => $this->getEmail()->toNullableString(),
            'phone' => $this->getPhone()->toNullableString(),
            'website' => $this->getWebsite()->toNullableString(),
            'status' => $this->getStatus()->toString(),
            'notes' => $this->getNotes()->toNullableString(),
            'is_sample' => $this->getIsSample()->toBoolean(),
        ];
    }

    public function getAbn(): Abn
    {
        return $this->abn;
    }

    public function setAbn(mixed $abn): self
    {
        $this->abn = new Abn($abn instanceof AbstractValue ? $abn->toValue() : $abn);

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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getIsSample(): IsSample
    {
        return $this->isSample;
    }

    public function setIsSample(mixed $isSample): self
    {
        $this->isSample = new IsSample($isSample instanceof AbstractValue ? $isSample->toValue() : $isSample);

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

    public function getNotes(): Notes
    {
        return $this->notes;
    }

    public function setNotes(mixed $notes): self
    {
        $this->notes = new Notes($notes instanceof AbstractValue ? $notes->toValue() : $notes);

        return $this;
    }

    public function getNumber(): Number
    {
        return $this->number;
    }

    public function setNumber(mixed $number): self
    {
        $this->number = new Number($number instanceof AbstractValue ? $number->toValue() : $number);

        return $this;
    }

    public function getPhone(): Phone
    {
        return $this->phone;
    }

    public function setPhone(mixed $phone): self
    {
        $this->phone = new Phone($phone instanceof AbstractValue ? $phone->toValue() : $phone);

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

    public function getWebsite(): Website
    {
        return $this->website;
    }

    public function setWebsite(mixed $website): self
    {
        $this->website = new Website($website instanceof AbstractValue ? $website->toValue() : $website);

        return $this;
    }

    public function getContactsCount(): ContactsCount
    {
        return $this->contactsCount;
    }
}
