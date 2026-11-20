<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionEloquentModel;
use Illuminate\Support\Facades\DB;

/**
 * Deletes bank feed lines. One at a time, so each removal is audited.
 */
final readonly class DeleteBankTransactions
{
    public function __construct(
        private BankTransactionCollection $transactions,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $ids = array_values($this->transactions->map(fn (BankTransaction $transaction): string => $transaction->getId()->toString())->all());

            BankTransactionEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($ids)
                ->get()
                ->each(fn (BankTransactionEloquentModel $model): ?bool => $model->delete());
        });
    }

    public static function lines(BankTransactionCollection $transactions): void
    {
        new self($transactions)->handle();
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
