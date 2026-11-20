<?php

declare(strict_types=1);

namespace App\Team;

use App\Shared\Abstracts\AbstractUuid;
use App\Team\Factories\TeamFactory;
use App\Team\Properties\BillingAddress;
use App\Team\Properties\BillingEmail;
use App\Team\Properties\BillingName;
use App\Team\Properties\BillingPhone;
use App\Team\Properties\BpayBillerCode;
use App\Team\Properties\CreatedAt;
use App\Team\Properties\Id;
use App\Team\Properties\InvoicePrefix;
use App\Team\Properties\IsPersonal;
use App\Team\Properties\Name;
use App\Team\Properties\OwnerId;
use App\Team\Properties\PaymentDetails;
use App\Team\Properties\TaxId;
use App\Team\Properties\TaxIdLabel;
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
        public BillingName $billingName,
        public TaxIdLabel $taxIdLabel,
        public TaxId $taxId,
        public BillingEmail $billingEmail,
        public BillingPhone $billingPhone,
        public BillingAddress $billingAddress,
        public PaymentDetails $paymentDetails,
        public BpayBillerCode $bpayBillerCode,
        public InvoicePrefix $invoicePrefix,
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

    public function getBillingName(): BillingName
    {
        return $this->billingName;
    }

    public function setBillingName(mixed $billingName): self
    {
        $this->billingName = new BillingName($billingName instanceof AbstractValue ? $billingName->toValue() : $billingName);

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

    public function getTaxId(): TaxId
    {
        return $this->taxId;
    }

    public function setTaxId(mixed $taxId): self
    {
        $this->taxId = new TaxId($taxId instanceof AbstractValue ? $taxId->toValue() : $taxId);

        return $this;
    }

    public function getBillingEmail(): BillingEmail
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(mixed $billingEmail): self
    {
        $this->billingEmail = new BillingEmail($billingEmail instanceof AbstractValue ? $billingEmail->toValue() : $billingEmail);

        return $this;
    }

    public function getBillingPhone(): BillingPhone
    {
        return $this->billingPhone;
    }

    public function setBillingPhone(mixed $billingPhone): self
    {
        $this->billingPhone = new BillingPhone($billingPhone instanceof AbstractValue ? $billingPhone->toValue() : $billingPhone);

        return $this;
    }

    public function getBillingAddress(): BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(mixed $billingAddress): self
    {
        $this->billingAddress = new BillingAddress($billingAddress instanceof AbstractValue ? $billingAddress->toValue() : $billingAddress);

        return $this;
    }

    public function getBpayBillerCode(): BpayBillerCode
    {
        return $this->bpayBillerCode;
    }

    public function setBpayBillerCode(mixed $bpayBillerCode): self
    {
        $this->bpayBillerCode = new BpayBillerCode($bpayBillerCode instanceof AbstractValue ? $bpayBillerCode->toValue() : $bpayBillerCode);

        return $this;
    }

    public function getPaymentDetails(): PaymentDetails
    {
        return $this->paymentDetails;
    }

    public function setPaymentDetails(mixed $paymentDetails): self
    {
        $this->paymentDetails = new PaymentDetails($paymentDetails instanceof AbstractValue ? $paymentDetails->toValue() : $paymentDetails);

        return $this;
    }

    public function getInvoicePrefix(): InvoicePrefix
    {
        return $this->invoicePrefix;
    }

    /**
     * An empty prefix is kept as an empty string: no prefix, rather than
     * the default.
     */
    public function setInvoicePrefix(mixed $invoicePrefix): self
    {
        $value = $invoicePrefix instanceof AbstractValue ? $invoicePrefix->toValue() : $invoicePrefix;
        $this->invoicePrefix = new InvoicePrefix($value ?? '');

        return $this;
    }

    /**
     * The name invoices are issued under: the billing name, or else the team's name.
     */
    public function getNameOnInvoices(): string
    {
        return $this->getBillingName()->toNullableString() ?? $this->getName()->toString();
    }

    /**
     * What the team prints on its invoices, for the billing settings and the invoice itself.
     *
     * @return array{name: string, billing_name: ?string, tax_id_label: ?string, tax_id: ?string, billing_email: ?string, billing_phone: ?string, billing_address: ?string, payment_details: ?string, bpay_biller_code: ?string, invoice_prefix: string}
     */
    public function toBillingSummary(): array
    {
        return [
            'name' => $this->getNameOnInvoices(),
            'billing_name' => $this->getBillingName()->toNullableString(),
            'tax_id_label' => $this->getTaxIdLabel()->toNullableString(),
            'tax_id' => $this->getTaxId()->toNullableString(),
            'billing_email' => $this->getBillingEmail()->toNullableString(),
            'billing_phone' => $this->getBillingPhone()->toNullableString(),
            'billing_address' => $this->getBillingAddress()->toNullableString(),
            'payment_details' => $this->getPaymentDetails()->toNullableString(),
            'bpay_biller_code' => $this->getBpayBillerCode()->toNullableString(),
            'invoice_prefix' => $this->getInvoicePrefix()->toNullableString() ?? InvoicePrefix::DEFAULT,
        ];
    }
}
