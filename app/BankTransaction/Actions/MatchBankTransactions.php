<?php

declare(strict_types=1);

namespace App\BankTransaction\Actions;

use App\BankRule\Actions\FindBankRule;
use App\BankTransaction\BankTransactionCollection;
use App\BankTransaction\Matching\CustomerMatcher;

/**
 * Applies the team's rules and works out the customer of pending lines that
 * have none yet, as an import does: such as after a rule or the customer
 * who paid has been added. A line a hide rule applies to is hidden.
 */
final readonly class MatchBankTransactions
{
    public function __construct(
        private BankTransactionCollection $transactions,
    ) {}

    /**
     * @return array{matched: int, hidden: int}
     */
    public function __invoke(): array
    {
        $matchers = [];
        $rules = null;
        $matched = 0;
        $hidden = [];

        foreach ($this->transactions as $transaction) {
            if (! $transaction->isPending() || $transaction->getCustomerId()->isNotNull()) {
                continue;
            }

            $account = $transaction->getBankAccount()->toData();
            $rules ??= FindBankRule::listForTeam($account->getTeamId()->toString());
            $description = $transaction->getDescription()->toString();

            if ($rules->hides($description)) {
                $hidden[] = $transaction;

                continue;
            }

            $matcher = $matchers[$account->getId()->toString()] ??= CustomerMatcher::forAccount($account, $rules);
            $match = $matcher->match($description);

            if ($match !== null) {
                AssignBankTransactionCustomer::to($transaction, $match->customerId, $match->matchedBy);
                $matched++;
            }
        }

        HideBankTransactions::lines(new BankTransactionCollection($hidden), true);

        return ['matched' => $matched, 'hidden' => count($hidden)];
    }

    /**
     * @return array{matched: int, hidden: int}
     */
    public static function lines(BankTransactionCollection $transactions): array
    {
        return new self($transactions)->handle();
    }

    /**
     * @return array{matched: int, hidden: int}
     */
    public function handle(): array
    {
        return $this->__invoke();
    }

    /**
     * @return array{matched: int, hidden: int}
     */
    public function execute(): array
    {
        return $this->__invoke();
    }
}
