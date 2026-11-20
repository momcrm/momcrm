<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\BankTransactionMatchEnum;
use App\BankTransaction\Properties\CustomerId;
use App\BankTransaction\Properties\MatchedBy;

/**
 * Says which customer a bank feed line is from, or that it isn't known.
 */
final readonly class AssignBankTransactionCustomer
{
    public function __construct(
        private BankTransaction $transaction,
        private ?string $customerId,
        private BankTransactionMatchEnum $matchedBy,
    ) {}

    public function __invoke(): void
    {
        BankTransactionEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->transaction->getId()->toString())
            ?->update([
                CustomerId::getDatabaseTableColumnName() => $this->customerId,
                MatchedBy::getDatabaseTableColumnName() => $this->customerId === null ? null : $this->matchedBy->value,
            ]);
    }

    public static function to(BankTransaction $transaction, ?string $customerId, BankTransactionMatchEnum $matchedBy = BankTransactionMatchEnum::Manual): void
    {
        new self($transaction, $customerId, $matchedBy)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
