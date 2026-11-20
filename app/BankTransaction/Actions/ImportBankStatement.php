<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankAccount\Actions\UpdateBankAccount;
use App\BankAccount\BankAccount;
use App\BankTransaction\BankTransactionEloquentModel;
use App\BankTransaction\Properties\AmountInCents;
use App\BankTransaction\Properties\BankAccountId;
use App\BankTransaction\Properties\CreatedAt;
use App\BankTransaction\Properties\Crn;
use App\BankTransaction\Properties\Description;
use App\BankTransaction\Properties\ExternalId;
use App\BankTransaction\Properties\Id;
use App\BankTransaction\Properties\PayerName;
use App\BankTransaction\Properties\PostedOn;
use App\BankTransaction\Properties\TeamId;
use App\BankTransaction\Properties\UpdatedAt;
use App\BankTransaction\Statements\CouldNotReadStatement;
use App\BankTransaction\Statements\CsvColumns;
use App\BankTransaction\Statements\ImportedStatement;
use App\BankTransaction\Statements\ReadStatement;
use App\BankTransaction\Statements\StatementFile;
use App\BankTransaction\Statements\StatementLine;
use App\BankTransaction\Statements\StatementNeedsColumns;
use Illuminate\Support\Facades\DB;

/**
 * Imports statement files into a bank account as bank feed lines. Only money
 * in is kept: money out is counted and skipped. A line already imported,
 * from this file or an earlier one, is skipped too.
 *
 * Every file is read before anything is saved, so a file that can't be read
 * leaves the account as it was.
 */
final readonly class ImportBankStatement
{
    private const int CHUNK = 500;

    /**
     * @param  list<array{name: string, contents: string}>  $files
     */
    public function __construct(
        private BankAccount $account,
        private array $files,
        private ?CsvColumns $columns = null,
    ) {}

    /**
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public function __invoke(): ImportedStatement
    {
        $statements = array_map(
            fn (array $file): ReadStatement => StatementFile::read($file['name'], $file['contents'], $this->account, $this->columns),
            $this->files,
        );

        return DB::transaction(function () use ($statements): ImportedStatement {
            $lines = array_merge(...array_map(fn (ReadStatement $statement): array => $statement->lines, $statements));
            $moneyIn = array_values(array_filter($lines, fn (StatementLine $line): bool => $line->isMoneyIn()));
            $imported = 0;

            foreach (array_chunk($moneyIn, self::CHUNK) as $chunk) {
                $imported += BankTransactionEloquentModel::query()
                    ->withoutGlobalScopes()
                    ->insertOrIgnore(array_map($this->row(...), $chunk));
            }

            $this->account->setLastImportedAt(now());

            $columns = $this->columns === null ? null : $statements[0]->columns ?? null;

            if ($columns !== null) {
                $this->account->setCsvColumns($columns->toStored());
            }

            UpdateBankAccount::fromBankAccount($this->account);

            return new ImportedStatement(
                imported: $imported,
                alreadyImported: count($moneyIn) - $imported,
                moneyOut: count(array_filter($lines, fn (StatementLine $line): bool => $line->amountInCents < 0)),
                unreadRows: array_sum(array_map(fn (ReadStatement $statement): int => $statement->unreadRows, $statements)),
            );
        });
    }

    /**
     * @param  list<array{name: string, contents: string}>  $files
     *
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public static function intoAccount(BankAccount $account, array $files, ?CsvColumns $columns = null): ImportedStatement
    {
        return new self($account, $files, $columns)->handle();
    }

    /**
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public function handle(): ImportedStatement
    {
        return $this->__invoke();
    }

    /**
     * @throws CouldNotReadStatement
     * @throws StatementNeedsColumns
     */
    public function execute(): ImportedStatement
    {
        return $this->__invoke();
    }

    /**
     * @return array<string, mixed>
     */
    private function row(StatementLine $line): array
    {
        $now = now();

        return [
            Id::getDatabaseTableColumnName() => Id::generate()->toString(),
            TeamId::getDatabaseTableColumnName() => $this->account->getTeamId()->toString(),
            BankAccountId::getDatabaseTableColumnName() => $this->account->getId()->toString(),
            PostedOn::getDatabaseTableColumnName() => $line->postedOn->toDateString(),
            AmountInCents::getDatabaseTableColumnName() => $line->amountInCents,
            Description::getDatabaseTableColumnName() => $line->description,
            ExternalId::getDatabaseTableColumnName() => mb_substr($line->externalId, 0, 191),
            Crn::getDatabaseTableColumnName() => $line->crn,
            PayerName::getDatabaseTableColumnName() => $line->payerName === null ? null : mb_substr($line->payerName, 0, 255),
            CreatedAt::getDatabaseTableColumnName() => $now,
            UpdatedAt::getDatabaseTableColumnName() => $now,
        ];
    }
}
