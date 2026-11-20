<?php

declare(strict_types=1);

namespace App\BankRule\Actions;

use App\BankRule\BankRule;
use App\BankRule\BankRuleEloquentModel;

final readonly class UpdateBankRule
{
    public function __construct(
        private BankRule $rule,
    ) {}

    public function __invoke(): BankRule
    {
        $model = BankRuleEloquentModel::query()
            ->withoutGlobalScopes()
            ->findOrFail($this->rule->getId()->toString());

        $model->update($this->rule->forDatabaseUpdate());

        return BankRule::fromEloquentModel($model->refresh());
    }

    public static function fromBankRule(BankRule $rule): BankRule
    {
        return new self($rule)->handle();
    }

    public function handle(): BankRule
    {
        return $this->__invoke();
    }

    public function execute(): BankRule
    {
        return $this->__invoke();
    }
}
