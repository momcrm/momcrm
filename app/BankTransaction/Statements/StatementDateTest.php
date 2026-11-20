<?php

declare(strict_types=1);

use App\BankTransaction\Statements\StatementDate;

it('reads the dates banks write', function (string $value, string $order, ?string $date): void {
    expect(StatementDate::parse($value, $order)?->toDateString())->toBe($date);
})->with([
    'day first' => ['03/11/2025', 'dmy', '2025-11-03'],
    'month first' => ['11/03/2025', 'mdy', '2025-11-03'],
    'two-digit year with dashes' => ['3-11-25', 'dmy', '2025-11-03'],
    'year first, whatever the order' => ['2025-11-03', 'mdy', '2025-11-03'],
    'packed, as in BPAY files' => ['20251103', 'dmy', '2025-11-03'],
    'with a time' => ['03/11/2025 14:22:01', 'dmy', '2025-11-03'],
    'month in words' => ['3 Nov 2025', 'dmy', '2025-11-03'],
    'month in words with dashes' => ['03-Nov-25', 'dmy', '2025-11-03'],
    'month in capitals' => ['03 NOV 2025', 'dmy', '2025-11-03'],
    'not a day' => ['31/02/2025', 'dmy', null],
    'not a date' => ['Opening balance', 'dmy', null],
]);

it('tells the order from the dates, when they show it', function (array $values, ?string $order): void {
    expect(StatementDate::orderOf($values))->toBe($order);
})->with([
    'a day after the 12th first' => [['01/11/2025', '13/11/2025'], 'dmy'],
    'a day after the 12th second' => [['11/01/2025', '11/13/2025'], 'mdy'],
    'year first' => [['2025-11-01'], 'ymd'],
    'every day the 12th or before' => [['01/11/2025', '12/11/2025'], null],
    'words' => [['3 Nov 2025'], 'dmy'],
]);
