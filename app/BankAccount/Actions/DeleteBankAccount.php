<?php

declare(strict_types=1);

namespace App\BankAccount\Actions;

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountEloquentModel;

/**
 * Deletes a bank account and the feed lines imported into it. Payments
 * already recorded from those lines stay.
 */
final readonly class DeleteBankAccount
{
    public function __construct(
        private BankAccount $account,
    ) {}

    public function __invoke(): void
    {
        BankAccountEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->account->getId()->toString())
            ?->delete();
    }

    public static function fromBankAccount(BankAccount $account): void
    {
        new self($account)->handle();
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
