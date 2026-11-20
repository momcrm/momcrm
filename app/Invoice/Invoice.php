<?php

declare(strict_types=1);

namespace App\Invoice;

use App\CustomField\Properties\CustomFieldValues;
use App\Invoice\Factories\InvoiceFactory;
use App\Invoice\Properties\CreatedAt;
use App\Invoice\Properties\Currency;
use App\Invoice\Properties\Customer;
use App\Invoice\Properties\CustomerId;
use App\Invoice\Properties\DueOn;
use App\Invoice\Properties\EmailedAt;
use App\Invoice\Properties\Id;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Items;
use App\Invoice\Properties\Notes;
use App\Invoice\Properties\Number;
use App\Invoice\Properties\PaidAt;
use App\Invoice\Properties\PaidInCents;
use App\Invoice\Properties\Prefix;
use App\Invoice\Properties\PurchaseOrder;
use App\Invoice\Properties\SentAt;
use App\Invoice\Properties\Status;
use App\Invoice\Properties\TaxRateBasisPoints;
use App\Invoice\Properties\TeamId;
use App\Invoice\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<InvoiceEloquentModel>
 */
final class Invoice extends AbstractData
{
    public function __construct(
        public CustomFieldValues $customFields,
        public CreatedAt $createdAt,
        public Currency $currency,
        public Customer $customer,
        public CustomerId $customerId,
        public DueOn $dueOn,
        public Id $id,
        public IssuedOn $issuedOn,
        public Items $items,
        public Notes $notes,
        public Number $number,
        public PaidAt $paidAt,
        public PaidInCents $paidInCents,
        public Prefix $prefix,
        public PurchaseOrder $purchaseOrder,
        public SentAt $sentAt,
        public EmailedAt $emailedAt,
        public Status $status,
        public TaxRateBasisPoints $taxRateBasisPoints,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): InvoiceFactory
    {
        return InvoiceEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): InvoiceCollection
    {
        if ($items instanceof InvoiceCollection) {
            return $items;
        }

        return InvoiceCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The number as shown on the invoice, with its team's prefix when it was
     * drafted, e.g. INV-0042.
     */
    public function getReference(): string
    {
        return ($this->getPrefix()->toNullableString() ?? '').str_pad((string) $this->getNumber()->toInteger(), 4, '0', STR_PAD_LEFT);
    }

    /**
     * The invoice number someone searched for: the last run of digits, so a
     * prefix with digits in it (INV2026-0042) still finds number 42. Null
     * when there are no digits.
     */
    public static function numberIn(string $search): ?int
    {
        return preg_match('/(\d+)\D*$/', $search, $matches) === 1 ? (int) $matches[1] : null;
    }

    public function isDraft(): bool
    {
        return $this->getStatus()->toEnum() === InvoiceStatusEnum::Draft;
    }

    /**
     * The totals of the invoice's line items, which must have been loaded.
     */
    public function totals(): InvoiceTotals
    {
        return InvoiceTotals::fromItems($this->getItems()->toDataCollection(), $this->getTaxRateBasisPoints()->toInteger());
    }

    /**
     * What is still owed once payments are taken off a total, never below 0.
     */
    public function balanceAfter(InvoiceTotals $totals): int
    {
        return max(0, $totals->totalInCents - $this->getPaidInCents()->toInteger());
    }

    /**
     * Only sent invoices are owed, so only they can be paid.
     */
    public function canTakePayments(): bool
    {
        return $this->getStatus()->toEnum() === InvoiceStatusEnum::Sent;
    }

    /**
     * Sent invoices whose due date has passed.
     */
    public function isOverdue(): bool
    {
        return $this->getStatus()->toEnum() === InvoiceStatusEnum::Sent
            && $this->getDueOn()->isNotNull()
            && $this->getDueOn()->toCarbon()->endOfDay()->isPast();
    }

    /**
     * @return array{id: string, number: int, reference: string, customer_id: string, issued_on: ?string, due_on: ?string, status: string, currency: string, is_overdue: bool, tax_rate_basis_points: int, purchase_order: ?string, notes: ?string, sent_at: ?string, emailed_at: ?string, paid_at: ?string, paid_in_cents: int, custom_fields: array<string, mixed>}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'reference' => $this->getReference(),
            'customer_id' => $this->getCustomerId()->toString(),
            'issued_on' => $this->getIssuedOn()->toNullableCarbon()?->toDateString(),
            'due_on' => $this->getDueOn()->toNullableCarbon()?->toDateString(),
            'status' => $this->getStatus()->toString(),
            'currency' => $this->getCurrency()->toEnum()->value,
            'is_overdue' => $this->isOverdue(),
            'tax_rate_basis_points' => $this->getTaxRateBasisPoints()->toInteger(),
            'purchase_order' => $this->getPurchaseOrder()->toNullableString(),
            'notes' => $this->getNotes()->toNullableString(),
            'sent_at' => $this->getSentAt()->toNullableISOString(),
            'emailed_at' => $this->getEmailedAt()->toNullableISOString(),
            'paid_at' => $this->getPaidAt()->toNullableISOString(),
            'paid_in_cents' => $this->getPaidInCents()->toInteger(),
            'custom_fields' => $this->getCustomFields()->toValues(),
        ];
    }

    public function getPrefix(): Prefix
    {
        return $this->prefix;
    }

    public function setPrefix(mixed $prefix): self
    {
        $this->prefix = new Prefix($prefix instanceof AbstractValue ? $prefix->toValue() : $prefix);

        return $this;
    }

    public function getPaidInCents(): PaidInCents
    {
        return $this->paidInCents;
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

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function setCustomerId(mixed $customerId): self
    {
        $this->customerId = new CustomerId($customerId instanceof AbstractValue ? $customerId->toValue() : $customerId);

        return $this;
    }

    public function getDueOn(): DueOn
    {
        return $this->dueOn;
    }

    public function setDueOn(mixed $dueOn): self
    {
        $this->dueOn = new DueOn($dueOn instanceof AbstractValue ? $dueOn->toValue() : $dueOn);

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

    public function getIssuedOn(): IssuedOn
    {
        return $this->issuedOn;
    }

    public function setIssuedOn(mixed $issuedOn): self
    {
        $this->issuedOn = new IssuedOn($issuedOn instanceof AbstractValue ? $issuedOn->toValue() : $issuedOn);

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

    public function getPaidAt(): PaidAt
    {
        return $this->paidAt;
    }

    public function setPaidAt(mixed $paidAt): self
    {
        $this->paidAt = new PaidAt($paidAt instanceof AbstractValue ? $paidAt->toValue() : $paidAt);

        return $this;
    }

    public function getPurchaseOrder(): PurchaseOrder
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(mixed $purchaseOrder): self
    {
        $this->purchaseOrder = new PurchaseOrder($purchaseOrder instanceof AbstractValue ? $purchaseOrder->toValue() : $purchaseOrder);

        return $this;
    }

    public function getEmailedAt(): EmailedAt
    {
        return $this->emailedAt;
    }

    public function getSentAt(): SentAt
    {
        return $this->sentAt;
    }

    public function setSentAt(mixed $sentAt): self
    {
        $this->sentAt = new SentAt($sentAt instanceof AbstractValue ? $sentAt->toValue() : $sentAt);

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

    public function getTaxRateBasisPoints(): TaxRateBasisPoints
    {
        return $this->taxRateBasisPoints;
    }

    public function setTaxRateBasisPoints(mixed $taxRateBasisPoints): self
    {
        $this->taxRateBasisPoints = new TaxRateBasisPoints($taxRateBasisPoints instanceof AbstractValue ? $taxRateBasisPoints->toValue() : $taxRateBasisPoints);

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

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getItems(): Items
    {
        return $this->items;
    }
}
