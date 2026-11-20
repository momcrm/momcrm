<?php

declare(strict_types=1);

namespace App\BankAccount\Actions;

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountEloquentModel;

final readonly class UpdateBankAccount
{
    public function __construct(
        private BankAccount $account,
    ) {}

    public function __invoke(): BankAccount
    {
        $model = BankAccountEloquentModel::query()
            ->withoutGlobalScopes()
            ->findOrFail($this->account->getId()->toString());

        $model->update($this->account->forDatabaseUpdate());

        return BankAccount::fromEloquentModel($model->refresh());
    }

    public static function fromBankAccount(BankAccount $account): BankAccount
    {
        return new self($account)->handle();
    }

    public function handle(): BankAccount
    {
        return $this->__invoke();
    }

    public function execute(): BankAccount
    {
        return $this->__invoke();
    }
}
