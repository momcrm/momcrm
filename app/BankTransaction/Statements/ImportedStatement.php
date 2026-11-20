<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

/**
 * What an import did: the new lines it added, and what it passed over.
 */
final readonly class ImportedStatement
{
    public function __construct(
        public int $imported,
        public int $alreadyImported,
        public int $moneyOut,
        public int $unreadRows,
    ) {}

    /**
     * "12 new deposits imported. 3 were already in; 40 payments out skipped."
     */
    public function summary(): string
    {
        $skipped = array_filter([
            $this->alreadyImported > 0 ? trans_choice(':count was already in|:count were already in', $this->alreadyImported) : null,
            $this->moneyOut > 0 ? trans_choice(':count payment out skipped|:count payments out skipped', $this->moneyOut) : null,
        ]);

        $imported = $this->imported === 0
            ? __('No new deposits.')
            : trans_choice(':count new deposit imported.|:count new deposits imported.', $this->imported);

        return $skipped === [] ? $imported : $imported.' '.ucfirst(implode('; ', $skipped)).'.';
    }
}
