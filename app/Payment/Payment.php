<?php

declare(strict_types=1);

namespace App\Payment;

use App\Payment\Factories\PaymentFactory;
use App\Payment\Properties\AllocatedInCents;
use App\Payment\Properties\Allocations;
use App\Payment\Properties\AmountInCents;
use App\Payment\Properties\CreatedAt;
use App\Payment\Properties\Currency;
use App\Payment\Properties\Customer;
use App\Payment\Properties\CustomerId;
use App\Payment\Properties\Id;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\Number;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
use App\Payment\Properties\TeamId;
use App\Payment\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * @extends AbstractData<PaymentEloquentModel>
 */
final class Payment extends AbstractData
{
    public function __construct(
        public AllocatedInCents $allocatedInCents,
        public Allocations $allocations,
        public AmountInCents $amountInCents,
        public CreatedAt $createdAt,
        public Currency $currency,
        public Customer $customer,
        public CustomerId $customerId,
        public Id $id,
        public Method $method,
        public Notes $notes,
        public Number $number,
        public PaidOn $paidOn,
        public Reference $reference,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): PaymentFactory
    {
        return PaymentEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): PaymentCollection
    {
        if ($items instanceof PaymentCollection) {
            return $items;
        }

        return PaymentCollection::make(parent::collect($items, $method)->all());
    }

    /**
     * The number as shown on receipts, e.g. PAY-0042.
     */
    public function getReferenceNumber(): string
    {
        return 'PAY-'.str_pad((string) $this->getNumber()->toInteger(), 4, '0', STR_PAD_LEFT);
    }

    /**
     * What is left of the payment after its allocations.
     */
    public function getUnallocatedInCents(): int
    {
        return max(0, $this->getAmountInCents()->toInteger() - $this->getAllocatedInCents()->toInteger());
    }

    /**
     * @return array{id: string, number: int, reference_number: string, customer_id: string, customer_name: ?string, paid_on: ?string, amount_in_cents: int, allocated_in_cents: int, unallocated_in_cents: int, currency: string, method: string, method_label: string, reference: ?string, notes: ?string, created_at: ?string}
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->getId()->toString(),
            'number' => $this->getNumber()->toInteger(),
            'reference_number' => $this->getReferenceNumber(),
            'customer_id' => $this->getCustomerId()->toString(),
            'customer_name' => $this->getCustomer()->toNullableData()?->getName()->toString(),
            'paid_on' => $this->getPaidOn()->toNullableCarbon()?->toDateString(),
            'amount_in_cents' => $this->getAmountInCents()->toInteger(),
            'allocated_in_cents' => $this->getAllocatedInCents()->toInteger(),
            'unallocated_in_cents' => $this->getUnallocatedInCents(),
            'currency' => $this->getCurrency()->toEnum()->value,
            'method' => $this->getMethod()->toEnum()->value,
            'method_label' => $this->getMethod()->toEnum()->label(),
            'reference' => $this->getReference()->toNullableString(),
            'notes' => $this->getNotes()->toNullableString(),
            'created_at' => $this->getCreatedAt()->toNullableISOString(),
        ];
    }

    public function getAllocatedInCents(): AllocatedInCents
    {
        return $this->allocatedInCents;
    }

    public function setAllocatedInCents(mixed $allocatedInCents): self
    {
        $this->allocatedInCents = new AllocatedInCents($allocatedInCents instanceof AbstractValue ? $allocatedInCents->toValue() : $allocatedInCents);

        return $this;
    }

    public function getAllocations(): Allocations
    {
        return $this->allocations;
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

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function setCurrency(mixed $currency): self
    {
        $this->currency = new Currency($currency instanceof AbstractValue ? $currency->toValue() : $currency);

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
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

    public function getId(): Id
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        $this->id = new Id($id instanceof AbstractValue ? $id->toValue() : $id);

        return $this;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function setMethod(mixed $method): self
    {
        $this->method = new Method($method instanceof AbstractValue ? $method->toValue() : $method);

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

    public function getPaidOn(): PaidOn
    {
        return $this->paidOn;
    }

    public function setPaidOn(mixed $paidOn): self
    {
        $this->paidOn = new PaidOn($paidOn instanceof AbstractValue ? $paidOn->toValue() : $paidOn);

        return $this;
    }

    public function getReference(): Reference
    {
        return $this->reference;
    }

    public function setReference(mixed $reference): self
    {
        $this->reference = new Reference($reference instanceof AbstractValue ? $reference->toValue() : $reference);

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
