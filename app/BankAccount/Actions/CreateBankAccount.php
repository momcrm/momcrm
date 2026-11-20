<?php

declare(strict_types=1);

namespace App\BankAccount\Actions;

use App\BankAccount\BankAccount;
use App\BankAccount\BankAccountEloquentModel;
use App\BankAccount\Properties\Id;

final readonly class CreateBankAccount
{
    public function __construct(
        private BankAccount $account,
    ) {}

    public function __invoke(): BankAccount
    {
        if ($this->account->getId()->isNull()) {
            $this->account->setId(Id::generate());
        }

        $model = BankAccountEloquentModel::query()->create($this->account->forDatabaseCreate());

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
