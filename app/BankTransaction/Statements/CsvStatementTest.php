<?php

declare(strict_types=1);

use App\BankTransaction\Statements\CouldNotReadStatement;
use App\BankTransaction\Statements\CsvColumns;
use App\BankTransaction\Statements\CsvStatement;
use App\BankTransaction\Statements\StatementLine;
use App\BankTransaction\Statements\StatementNeedsColumns;
use App\Shared\CurrencyEnum;

/**
 * @return list<array{0: string, 1: int, 2: string}>
 */
function statement_lines(string $csv, ?CsvColumns $given = null, ?CsvColumns $saved = null): array
{
    return array_map(
        fn (StatementLine $line): array => [$line->postedOn->toDateString(), $line->amountInCents, $line->description],
        CsvStatement::fromString('statement.csv', $csv)->read(CurrencyEnum::AUD, $given, $saved)->lines,
    );
}

it('reads debit and credit columns by their headings, money in as positive', function (): void {
    $csv = "Process date,Description,Currency Code, Debit, Credit, Balance\n"
        ."3/5/2021,Transfer to CBA A/c NetBank example,AUD,12201.86,,0\n"
        ."30/04/2021,Transfer from J CITIZEN NetBank EXAMPLE,AUD,,\"1,444.00\",12201.86\n";

    expect(statement_lines($csv))->toBe([
        ['2021-05-03', -1220186, 'Transfer to CBA A/c NetBank example'],
        ['2021-04-30', 144400, 'Transfer from J CITIZEN NetBank EXAMPLE'],
    ]);
});

it('reads a signed amount column and joins every description column', function (): void {
    $csv = "Date,Amount,Payee,Description,Reference\n"
        ."2025-11-03,250.00,Harbour Dental,Invoice payment,INV-0042\n"
        ."2025-11-04,-19.95,Telstra,Phone,\n";

    expect(statement_lines($csv))->toBe([
        ['2025-11-03', 25000, 'Harbour Dental Invoice payment INV-0042'],
        ['2025-11-04', -1995, 'Telstra Phone'],
    ]);
});

it('passes over rows that are not transactions', function (): void {
    $read = CsvStatement::fromString('s.csv', "Date,Description,Amount\nOpening balance,,\n13/11/2025,Deposit,10.00\nTotal,,10.00\n")
        ->read(CurrencyEnum::AUD);

    expect($read->lines)->toHaveCount(1)->and($read->unreadRows)->toBe(2);
});

it('tells identical rows apart, the same way every time the file is read', function (): void {
    $csv = "Date,Description,Amount\n13/11/2025,Cash deposit,50.00\n13/11/2025,Cash deposit,50.00\n";
    $ids = fn (): array => array_map(fn (StatementLine $line): string => $line->externalId, CsvStatement::fromString('s.csv', $csv)->read(CurrencyEnum::AUD)->lines);

    expect($ids()[0])->not->toBe($ids()[1])
        ->and($ids())->toBe($ids());
});

it('asks for the columns of a file without headings, with a guess', function (): void {
    try {
        CsvStatement::fromString('cba.csv', "13/11/2025,\"+250.00\",\"Direct Credit 062000 HARBOUR DENTAL\",\"+1250.00\"\n14/11/2025,\"-20.00\",\"Card purchase\",\"+1230.00\"\n")->read(CurrencyEnum::AUD);
    } catch (StatementNeedsColumns $exception) {
        expect($exception->toArray())->toMatchArray([
            'file' => 'cba.csv',
            'headings' => ['Column 1', 'Column 2', 'Column 3', 'Column 4'],
        ])->and($exception->toArray()['guess'])->toMatchArray([
            'has_header' => false,
            'date' => 0,
            'amount' => 1,
            'description' => [2],
            'date_order' => 'dmy',
            'layout' => 'columns:4',
        ]);

        return;
    }

    $this->fail('The columns were not asked for.');
});

it('asks which way round dates go when every day could be a month', function (): void {
    expect(fn () => statement_lines("Date,Description,Amount\n03/11/2025,Deposit,10.00\n"))
        ->toThrow(StatementNeedsColumns::class);
});

it('reads with the columns given, and with saved columns when the layout matches', function (): void {
    $csv = "03/11/2025,10.00,Deposit,10.00\n";
    $columns = new CsvColumns(hasHeader: false, date: 0, description: [2], amount: 1, credit: null, debit: null, dateOrder: 'mdy');

    expect(statement_lines($csv, given: $columns))->toBe([['2025-03-11', 1000, 'Deposit']])
        ->and(statement_lines($csv, saved: $columns->withLayout('columns:4')))->toBe([['2025-03-11', 1000, 'Deposit']])
        ->and(fn () => statement_lines($csv, saved: $columns->withLayout('columns:5')))->toThrow(StatementNeedsColumns::class);
});

it('refuses a file in another currency, an empty file and one with no transactions', function (string $csv, string $message): void {
    expect(fn () => statement_lines($csv))->toThrow(CouldNotReadStatement::class, $message);
})->with([
    'another currency' => ["Date,Description,Currency,Amount\n13/11/2025,Deposit,USD,10.00\n", 'statement.csv: it is in USD but the account holds AUD.'],
    'empty' => ["\xEF\xBB\xBF\n\n", 'statement.csv: it is empty.'],
    'no transactions' => ["Date,Description,Amount\nnot a date,Deposit,10.00\n", 'statement.csv: no transactions were found in it.'],
]);
