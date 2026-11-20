<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\Matching\CustomerMatcher;

/**
 * Works out the customer of pending lines that have none yet, as an import
 * does, such as after the customer who paid has been added.
 */
final readonly class MatchBankTransactions
{
    public function __construct(
        private BankTransactionCollection $transactions,
    ) {}

    /**
     * @return int how many lines were matched
     */
    public function __invoke(): int
    {
        $matchers = [];
        $matched = 0;

        foreach ($this->transactions as $transaction) {
            if (! $transaction->isPending() || $transaction->getCustomerId()->isNotNull()) {
                continue;
            }

            $account = $transaction->getBankAccount()->toData();
            $matcher = $matchers[$account->getId()->toString()] ??= CustomerMatcher::forAccount($account);
            $match = $matcher->match($transaction->getDescription()->toString());

            if ($match !== null) {
                AssignBankTransactionCustomer::to($transaction, $match->customerId, $match->matchedBy);
                $matched++;
            }
        }

        return $matched;
    }

    public static function lines(BankTransactionCollection $transactions): int
    {
        return new self($transactions)->handle();
    }

    public function handle(): int
    {
        return $this->__invoke();
    }

    public function execute(): int
    {
        return $this->__invoke();
    }
}
