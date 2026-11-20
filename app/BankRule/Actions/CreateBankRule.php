<?php

declare(strict_types=1);

namespace App\BankRule\Actions;

use App\BankRule\BankRule;
use App\BankRule\BankRuleEloquentModel;
use App\BankRule\Properties\Id;

final readonly class CreateBankRule
{
    public function __construct(
        private BankRule $rule,
    ) {}

    public function __invoke(): BankRule
    {
        if ($this->rule->getId()->isNull()) {
            $this->rule->setId(Id::generate());
        }

        $model = BankRuleEloquentModel::query()->create($this->rule->forDatabaseCreate());

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
