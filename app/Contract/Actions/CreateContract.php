<?php

declare(strict_types=1);

namespace App\Contract\Actions;

use App\Contract\Contract;
use App\Contract\ContractEloquentModel;
use App\Contract\Properties\Id;

final readonly class CreateContract
{
    public function __construct(
        private Contract $contract,
    ) {}

    public function __invoke(): Contract
    {
        if ($this->contract->getId()->isNull()) {
            $this->contract->setId(Id::generate());
        }

        $model = ContractEloquentModel::query()->create($this->contract->forDatabaseCreate());

        return Contract::fromEloquentModel($model->refresh());
    }

    public static function fromContract(Contract $contract): Contract
    {
        return new self($contract)->handle();
    }

    public function handle(): Contract
    {
        return $this->__invoke();
    }

    public function execute(): Contract
    {
        return $this->__invoke();
    }
}
