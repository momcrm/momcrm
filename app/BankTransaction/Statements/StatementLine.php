<?php

declare(strict_types=1);

namespace App\BankTransaction\Statements;

use Carbon\CarbonImmutable;

/**
 * One transaction read from a statement file. A positive amount is money
 * in, a negative one money out.
 */
final readonly class StatementLine
{
    public function __construct(
        public CarbonImmutable $postedOn,
        public int $amountInCents,
        public string $description,
        public string $externalId,
        public ?string $crn = null,
        public ?string $payerName = null,
    ) {}

    public function isMoneyIn(): bool
    {
        return $this->amountInCents > 0;
    }
}
