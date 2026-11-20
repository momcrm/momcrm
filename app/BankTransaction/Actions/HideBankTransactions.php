<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransaction;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\Properties\HiddenAt;
use Illuminate\Support\Facades\DB;

/**
 * Hides bank feed lines, or brings hidden ones back to pending. One at a
 * time, so each change is audited.
 */
final readonly class HideBankTransactions
{
    public function __construct(
        private BankTransactionCollection $transactions,
        private bool $isHidden,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            $ids = array_values($this->transactions->map(fn (BankTransaction $transaction): string => $transaction->getId()->toString())->all());

            BankTransactionEloquentModel::query()
                ->withoutGlobalScopes()
                ->whereKey($ids)
                ->get()
                ->each(fn (BankTransactionEloquentModel $model): bool => $model->update([
                    HiddenAt::getDatabaseTableColumnName() => $this->isHidden ? ($model->hidden_at ?? now()) : null,
                ]));
        });
    }

    public static function lines(BankTransactionCollection $transactions, bool $isHidden): void
    {
        new self($transactions, $isHidden)->handle();
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
