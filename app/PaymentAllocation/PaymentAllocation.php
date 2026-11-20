<?php

declare(strict_types=1);

namespace App\PaymentAllocation;

use App\PaymentAllocation\Factories\PaymentAllocationFactory;
use App\PaymentAllocation\Properties\AmountInCents;
use App\PaymentAllocation\Properties\CreatedAt;
use App\PaymentAllocation\Properties\Id;
use App\PaymentAllocation\Properties\Invoice;
use App\PaymentAllocation\Properties\InvoiceId;
use App\PaymentAllocation\Properties\PaymentId;
use App\PaymentAllocation\Properties\TeamId;
use App\PaymentAllocation\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<PaymentAllocationEloquentModel>
 */
final class PaymentAllocation extends AbstractData
{
    public function __construct(
        public AmountInCents $amountInCents,
        public CreatedAt $createdAt,
        public Id $id,
        public Invoice $invoice,
        public InvoiceId $invoiceId,
        public PaymentId $paymentId,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): PaymentAllocationFactory
    {
        return PaymentAllocationEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): PaymentAllocationCollection
    {
        if ($items instanceof PaymentAllocationCollection) {
            return $items;
        }

        return PaymentAllocationCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * @return array{id: string, payment_id: string, invoice_id: string, invoice_reference: ?string, amount_in_cents: int}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'payment_id' => $this->getPaymentId()->toString(),
            'invoice_id' => $this->getInvoiceId()->toString(),
            'invoice_reference' => $this->getInvoice()->toNullableData()?->getReference(),
            'amount_in_cents' => $this->getAmountInCents()->toInteger(),
        ];
    }

    public function getAmountInCents(): AmountInCents
    {
        return $this->amountInCents;
    }

    public function setAmountInCents(mixed $amountInCents): self
    {
        $this->amountInCents = new AmountInCents($amountInCents instanceof AbstractValue ? $amountInCents->toValue() : $amountInCents);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
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

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getInvoiceId(): InvoiceId
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(mixed $invoiceId): self
    {
        $this->invoiceId = new InvoiceId($invoiceId instanceof AbstractValue ? $invoiceId->toValue() : $invoiceId);

        return $this;
    }

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function setPaymentId(mixed $paymentId): self
    {
        $this->paymentId = new PaymentId($paymentId instanceof AbstractValue ? $paymentId->toValue() : $paymentId);

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
}
