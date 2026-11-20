<?php

declare(strict_types=1);

namespace App\BankAccount;

use App\Shared\Abstracts\AbstractDataCollection;

/**
 * @extends AbstractDataCollection<int, BankAccount>
 */
final class BankAccountCollection extends AbstractDataCollection
{
    public static function dataClass(): string
    {
        return BankAccount::class;
    }

    /**
     * @return list<array{id: string, name: string, kind: string, kind_label: string, currency: string, last_imported_at: ?string}>
     */
    public function toSummaries(): array
    {
        return array_values($this->map(fn (BankAccount $account): array => $account->toSummary())->all());
    }
}
