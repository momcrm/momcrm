<?php

declare(strict_types=1);

namespace App\Contact;

use App\Contact\Factories\ContactFactory;
use App\Contact\Properties\ContactRoleId;
use App\Contact\Properties\CreatedAt;
use App\Contact\Properties\Customer;
use App\Contact\Properties\CustomerId;
use App\Contact\Properties\Email;
use App\Contact\Properties\FirstName;
use App\Contact\Properties\Id;
use App\Contact\Properties\IsPrimary;
use App\Contact\Properties\LastName;
use App\Contact\Properties\MobilePhone;
use App\Contact\Properties\PortalAccess;
use App\Contact\Properties\Position;
use App\Contact\Properties\ReceivesInvoices;
use App\Contact\Properties\TeamId;
use App\Contact\Properties\UpdatedAt;
use App\Contact\Properties\WorkPhone;
use App\CustomField\Properties\CustomFieldValues;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<ContactEloquentModel>
 */
final class Contact extends AbstractData
{
    public function __construct(
        public ContactRoleId $contactRoleId,
        public CreatedAt $createdAt,
        public CustomFieldValues $customFields,
        public Customer $customer,
        public CustomerId $customerId,
        public Email $email,
        public FirstName $firstName,
        public Id $id,
        public IsPrimary $isPrimary,
        public ReceivesInvoices $receivesInvoices,
        public LastName $lastName,
        public MobilePhone $mobilePhone,
        public PortalAccess $portalAccess,
        public Position $position,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
        public WorkPhone $workPhone,
    ) {}

    public static function getFactory(): ContactFactory
    {
        return ContactEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): ContactCollection
    {
        if ($items instanceof ContactCollection) {
            return $items;
        }

        return ContactCollection::make(parent::collect($items, $method)->all());
    }

    public function getFullName(): string
    {
        return trim($this->getFirstName()->toString().' '.$this->getLastName()->toString());
    }

    /**
     * The fields the contact pages show.
     *
     * @return array{id: string, customer_id: string, first_name: string, last_name: ?string, full_name: string, email: ?string, position: ?string, work_phone: ?string, mobile_phone: ?string, is_primary: bool, receives_invoices: bool, contact_role_id: ?string, portal_access: bool, custom_fields: array<string, mixed>}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'customer_id' => $this->getCustomerId()->toString(),
            'first_name' => $this->getFirstName()->toString(),
            'last_name' => $this->getLastName()->toNullableString(),
            'full_name' => $this->getFullName(),
            'email' => $this->getEmail()->toNullableString(),
            'position' => $this->getPosition()->toNullableString(),
            'work_phone' => $this->getWorkPhone()->toNullableString(),
            'mobile_phone' => $this->getMobilePhone()->toNullableString(),
            'is_primary' => $this->getIsPrimary()->toBoolean(),
            'receives_invoices' => $this->getReceivesInvoices()->toBoolean(),
            'contact_role_id' => $this->getContactRoleId()->toNullableString(),
            'portal_access' => $this->getPortalAccess()->toBoolean(),
            'custom_fields' => $this->getCustomFields()->toValues(),
        ];
    }

    public function getCustomFields(): CustomFieldValues
    {
        return $this->customFields;
    }

    public function setCustomFields(mixed $customFields): self
    {
        $this->customFields = new CustomFieldValues($customFields instanceof AbstractValue ? $customFields->toValue() : $customFields);

        return $this;
    }

    public function getContactRoleId(): ContactRoleId
    {
        return $this->contactRoleId;
    }

    public function setContactRoleId(mixed $contactRoleId): self
    {
        $this->contactRoleId = new ContactRoleId($contactRoleId instanceof AbstractValue ? $contactRoleId->toValue() : $contactRoleId);

        return $this;
    }

    public function getPortalAccess(): PortalAccess
    {
        return $this->portalAccess;
    }

    public function setPortalAccess(mixed $portalAccess): self
    {
        $this->portalAccess = new PortalAccess($portalAccess instanceof AbstractValue ? $portalAccess->toValue() : $portalAccess);

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

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function setEmail(mixed $email): self
    {
        $this->email = new Email($email instanceof AbstractValue ? $email->toValue() : $email);

        return $this;
    }

    public function getFirstName(): FirstName
    {
        return $this->firstName;
    }

    public function setFirstName(mixed $firstName): self
    {
        $this->firstName = new FirstName($firstName instanceof AbstractValue ? $firstName->toValue() : $firstName);

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

    public function getReceivesInvoices(): ReceivesInvoices
    {
        return $this->receivesInvoices;
    }

    public function setReceivesInvoices(mixed $receivesInvoices): self
    {
        $this->receivesInvoices = new ReceivesInvoices($receivesInvoices instanceof AbstractValue ? $receivesInvoices->toValue() : $receivesInvoices);

        return $this;
    }

    public function getLastName(): LastName
    {
        return $this->lastName;
    }

    public function setLastName(mixed $lastName): self
    {
        $this->lastName = new LastName($lastName instanceof AbstractValue ? $lastName->toValue() : $lastName);

        return $this;
    }

    public function getMobilePhone(): MobilePhone
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(mixed $mobilePhone): self
    {
        $this->mobilePhone = new MobilePhone($mobilePhone instanceof AbstractValue ? $mobilePhone->toValue() : $mobilePhone);

        return $this;
    }

    public function getPosition(): Position
    {
        return $this->position;
    }

    public function setPosition(mixed $position): self
    {
        $this->position = new Position($position instanceof AbstractValue ? $position->toValue() : $position);

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

    public function getWorkPhone(): WorkPhone
    {
        return $this->workPhone;
    }

    public function setWorkPhone(mixed $workPhone): self
    {
        $this->workPhone = new WorkPhone($workPhone instanceof AbstractValue ? $workPhone->toValue() : $workPhone);

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }
}
