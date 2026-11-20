<?php

declare(strict_types=1);

namespace App\BankTransaction;

use App\BankTransaction\Factories\BankTransactionFactory;
use App\BankTransaction\Properties\AmountInCents;
use App\BankTransaction\Properties\BankAccount;
use App\BankTransaction\Properties\BankAccountId;
use App\BankTransaction\Properties\CreatedAt;
use App\BankTransaction\Properties\Crn;
use App\BankTransaction\Properties\Customer;
use App\BankTransaction\Properties\CustomerId;
use App\BankTransaction\Properties\Description;
use App\BankTransaction\Properties\ExternalId;
use App\BankTransaction\Properties\HiddenAt;
use App\BankTransaction\Properties\Id;
use App\BankTransaction\Properties\MatchedBy;
use App\BankTransaction\Properties\PayerName;
use App\BankTransaction\Properties\PostedOn;
use App\BankTransaction\Properties\TeamId;
use App\BankTransaction\Properties\UpdatedAt;
use Illuminate\Support\Collection;
use Mom\Data\AbstractData;
use Mom\Data\AbstractValue;

/**
 * One deposit from an imported bank statement: money that came into one of
 * the team's accounts, waiting to be matched to the customer who paid it.
 *
 * @extends AbstractData<BankTransactionEloquentModel>
 */
final class BankTransaction extends AbstractData
{
    public function __construct(
        public AmountInCents $amountInCents,
        public BankAccount $bankAccount,
        public BankAccountId $bankAccountId,
        public CreatedAt $createdAt,
        public Crn $crn,
        public Customer $customer,
        public CustomerId $customerId,
        public Description $description,
        public ExternalId $externalId,
        public HiddenAt $hiddenAt,
        public Id $id,
        public MatchedBy $matchedBy,
        public PayerName $payerName,
        public PostedOn $postedOn,
        public TeamId $teamId,
        public UpdatedAt $updatedAt,
    ) {}

    public static function getFactory(): BankTransactionFactory
    {
        return BankTransactionEloquentModel::factory();
    }

    public function getPrimaryKey(): AbstractValue
    {
        return $this->getId();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $items
     */
    public static function collect(Collection|array $items, string $method = 'fromData'): BankTransactionCollection
    {
        if ($items instanceof BankTransactionCollection) {
            return $items;
        }

        return BankTransactionCollection::make(parent::collect($items, $method)->all());
    }

    public function status(): BankTransactionStatusEnum
    {
        return $this->getHiddenAt()->isNotNull() ? BankTransactionStatusEnum::Hidden : BankTransactionStatusEnum::Pending;
    }

    /**
     * The fields the bank feed list shows, with its account and customer
     * when they were loaded.
     *
     * @return array{id: string, bank_account_id: string, bank_account_name: ?string, currency: ?string, posted_on: ?string, amount_in_cents: int, description: string, crn: ?string, payer_name: ?string, customer_id: ?string, customer_name: ?string, matched_by: ?string, matched_by_label: ?string, status: string, status_label: string}
     */
    public function toSummary(): array
    {
        $account = $this->getBankAccount()->toNullableData();
        $matchedBy = $this->getMatchedBy()->toNullableEnum();

        return [
            'id' => $this->getId()->toString(),
            'bank_account_id' => $this->getBankAccountId()->toString(),
            'bank_account_name' => $account?->getName()->toString(),
            'currency' => $account?->getCurrency()->toEnum()->value,
            'posted_on' => $this->getPostedOn()->toNullableCarbon()?->toDateString(),
            'amount_in_cents' => $this->getAmountInCents()->toInteger(),
            'description' => $this->getDescription()->toString(),
            'crn' => $this->getCrn()->toNullableString(),
            'payer_name' => $this->getPayerName()->toNullableString(),
            'customer_id' => $this->getCustomerId()->toNullableString(),
            'customer_name' => $this->getCustomer()->toNullableData()?->getName()->toString(),
            'matched_by' => $matchedBy?->value,
            'matched_by_label' => $matchedBy?->label(),
            'status' => $this->status()->value,
            'status_label' => $this->status()->label(),
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

    public function getBankAccount(): BankAccount
    {
        return $this->bankAccount;
    }

    public function getBankAccountId(): BankAccountId
    {
        return $this->bankAccountId;
    }

    public function setBankAccountId(mixed $bankAccountId): self
    {
        $this->bankAccountId = new BankAccountId($bankAccountId instanceof AbstractValue ? $bankAccountId->toValue() : $bankAccountId);

        return $this;
    }

    public function getCreatedAt(): CreatedAt
    {
        return $this->createdAt;
    }

    public function getCrn(): Crn
    {
        return $this->crn;
    }

    public function setCrn(mixed $crn): self
    {
        $this->crn = new Crn($crn instanceof AbstractValue ? $crn->toValue() : $crn);

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

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(mixed $description): self
    {
        $this->description = new Description($description instanceof AbstractValue ? $description->toValue() : $description);

        return $this;
    }

    public function getExternalId(): ExternalId
    {
        return $this->externalId;
    }

    public function setExternalId(mixed $externalId): self
    {
        $this->externalId = new ExternalId($externalId instanceof AbstractValue ? $externalId->toValue() : $externalId);

        return $this;
    }

    public function getHiddenAt(): HiddenAt
    {
        return $this->hiddenAt;
    }

    public function setHiddenAt(mixed $hiddenAt): self
    {
        $this->hiddenAt = new HiddenAt($hiddenAt instanceof AbstractValue ? $hiddenAt->toValue() : $hiddenAt);

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

    public function getMatchedBy(): MatchedBy
    {
        return $this->matchedBy;
    }

    public function setMatchedBy(mixed $matchedBy): self
    {
        $this->matchedBy = new MatchedBy($matchedBy instanceof AbstractValue ? $matchedBy->toValue() : $matchedBy);

        return $this;
    }

    public function getPayerName(): PayerName
    {
        return $this->payerName;
    }

    public function setPayerName(mixed $payerName): self
    {
        $this->payerName = new PayerName($payerName instanceof AbstractValue ? $payerName->toValue() : $payerName);

        return $this;
    }

    public function getPostedOn(): PostedOn
    {
        return $this->postedOn;
    }

    public function setPostedOn(mixed $postedOn): self
    {
        $this->postedOn = new PostedOn($postedOn instanceof AbstractValue ? $postedOn->toValue() : $postedOn);

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
