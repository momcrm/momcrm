<?php

declare(strict_types=1);

use App\BankTransaction\Statements\StatementAmount;

it('reads amounts the way banks write them', function (string $value, ?int $cents): void {
    expect(StatementAmount::toCents($value))->toBe($cents);
})->with([
    'plain' => ['1250.00', 125000],
    'thousands and a symbol' => ['$1,250.50', 125050],
    'signed' => ['+250.00', 25000],
    'negative' => ['-19.95', -1995],
    'in brackets' => ['(19.95)', -1995],
    'debit marker' => ['19.95 DR', -1995],
    'credit marker' => ['19.95 CR', 1995],
    'whole dollars' => ['40', 4000],
    'blank' => ['', null],
    'not an amount' => ['n/a', null],
]);
