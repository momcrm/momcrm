<?php

declare(strict_types=1);

namespace App\BankTransaction\Matching;

use App\BankTransaction\BankTransactionMatchEnum;

/**
 * The customer a deposit was matched to, and how.
 */
final readonly class CustomerMatch
{
    public function __construct(
        public string $customerId,
        public BankTransactionMatchEnum $matchedBy,
    ) {}
}
