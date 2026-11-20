<?php

declare(strict_types=1);

namespace App\Customer;

use App\Customer\Factories\CustomerFactory;
use App\Customer\Properties\BillingType;
use App\Customer\Properties\ContactsCount;
use App\Customer\Properties\CreatedAt;
use App\Customer\Properties\Currency;
use App\Customer\Properties\DaysToPay;
use App\Customer\Properties\Email;
use App\Customer\Properties\Id;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Number;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Prefix;
use App\Customer\Properties\ServicesCount;
use App\Customer\Properties\Status;
use App\Customer\Properties\TaxId;
use App\Customer\Properties\TaxIdLabel;
use App\Customer\Properties\TeamId;
use App\Customer\Properties\TypeId;
use App\Customer\Properties\UpdatedAt;
use App\Customer\Properties\Website;
use App\CustomField\Properties\CustomFieldValues;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<CustomerEloquentModel>
 */
final class Customer extends AbstractData
{
    public function __construct(
        public BillingType $billingType,
        public ContactsCount $contactsCount,
        public CreatedAt $createdAt,
        public Currency $currency,
        public CustomFieldValues $customFields,
        public DaysToPay $daysToPay,
        public Email $email,
        public Id $id,
        public IsSample $isSample,
        public Name $name,
        public Notes $notes,
        public Number $number,
        public Phone $phone,
        public Prefix $prefix,
        public ServicesCount $servicesCount,
        public Status $status,
        public TaxId $taxId,
        public TaxIdLabel $taxIdLabel,
        public TeamId $teamId,
        public TypeId $typeId,
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
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): CustomerCollection
    {
        if ($items instanceof CustomerCollection) {
            return $items;
        }

        return CustomerCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The fields the customer pages show.
     *
     * @return array{id: string, number: int, name: string, prefix: ?string, type_id: ?string, tax_id: ?string, tax_id_label: ?string, email: ?string, phone: ?string, website: ?string, status: string, billing_type: string, days_to_pay: ?int, currency: string, notes: ?string, is_sample: bool, custom_fields: array<string, mixed>}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'name' => $this->getName()->toString(),
            'prefix' => $this->getPrefix()->toNullableString(),
            'type_id' => $this->getTypeId()->toNullableString(),
            'tax_id' => $this->getTaxId()->toNullableString(),
            'tax_id_label' => $this->getTaxIdLabel()->toNullableString(),
            'email' => $this->getEmail()->toNullableString(),
            'phone' => $this->getPhone()->toNullableString(),
            'website' => $this->getWebsite()->toNullableString(),
            'status' => $this->getStatus()->toString(),
            'billing_type' => $this->getBillingType()->toEnum()->value,
            'days_to_pay' => $this->getDaysToPay()->toNullableInteger(),
            'currency' => $this->getCurrency()->toEnum()->value,
            'notes' => $this->getNotes()->toNullableString(),
            'is_sample' => $this->getIsSample()->toBoolean(),
            'custom_fields' => $this->getCustomFields()->toValues(),
        ];
    }

    /**
     * The tax ID as printed, e.g. "ABN 12 345 678 901", or null without one.
     */
    public function taxIdLine(): ?string
    {
        if ($this->getTaxId()->isNull()) {
            return null;
        }

        return ($this->getTaxIdLabel()->toNullableString() ?? __('Tax ID')).' '.$this->getTaxId()->toString();
    }

    public function getTaxId(): TaxId
    {
        return $this->taxId;
    }

    public function setTaxId(mixed $taxId): self
    {
        $this->taxId = new TaxId($taxId instanceof AbstractValue ? $taxId->toValue() : $taxId);

        return $this;
    }

    public function getTaxIdLabel(): TaxIdLabel
    {
        return $this->taxIdLabel;
    }

    public function setTaxIdLabel(mixed $taxIdLabel): self
    {
        $this->taxIdLabel = new TaxIdLabel($taxIdLabel instanceof AbstractValue ? $taxIdLabel->toValue() : $taxIdLabel);

        return $this;
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

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(mixed $currency): self
    {
        $this->currency = new Currency($currency instanceof AbstractValue ? $currency->toValue() : $currency);

        return $this;
    }

    public function getDaysToPay(): DaysToPay
    {
        return $this->daysToPay;
    }

    public function setDaysToPay(mixed $daysToPay): self
    {
        $this->daysToPay = new DaysToPay($daysToPay instanceof AbstractValue ? $daysToPay->toValue() : $daysToPay);

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

    public function getBillingType(): BillingType
    {
        return $this->billingType;
    }

    public function setBillingType(mixed $billingType): self
    {
        $this->billingType = new BillingType($billingType instanceof AbstractValue ? $billingType->toValue() : $billingType);

        return $this;
    }

    public function getPrefix(): Prefix
    {
        return $this->prefix;
    }

    public function setPrefix(mixed $prefix): self
    {
        $this->prefix = new Prefix(Prefix::normalise($prefix instanceof AbstractValue ? $prefix->toValue() : $prefix));

        return $this;
    }

    public function getServicesCount(): ServicesCount
    {
        return $this->servicesCount;
    }

    public function getTypeId(): TypeId
    {
        return $this->typeId;
    }

    public function setTypeId(mixed $typeId): self
    {
        $this->typeId = new TypeId($typeId instanceof AbstractValue ? $typeId->toValue() : $typeId);

        return $this;
    }
}
