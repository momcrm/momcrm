<?php

declare(strict_types=1);

namespace App\BankRule\Actions;

use App\BankRule\BankRule;
use App\BankRule\BankRuleEloquentModel;

/**
 * Deletes a bank feed rule. Lines it already matched or hid stay as they are.
 */
final readonly class DeleteBankRule
{
    public function __construct(
        private BankRule $rule,
    ) {}

    public function __invoke(): void
    {
        BankRuleEloquentModel::query()
            ->withoutGlobalScopes()
            ->find($this->rule->getId()->toString())
            ?->delete();
    }

    public static function fromBankRule(BankRule $rule): void
    {
        new self($rule)->handle();
    }

    public function handle(): void
    {
        $this->__invoke();
    }

    public function execute(): void
    {
        $this->__invoke();
    }
}
