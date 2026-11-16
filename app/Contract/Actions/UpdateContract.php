<?php

declare(strict_types=1);

namespace App\Contract\Actions;

use App\Contract\Contract;
use App\Contract\ContractEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateContract
{
    public function __construct(
        private Contract $contract,
    ) {}

    public function __invoke(): Contract
    {
        ContractEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->contract->getId()->toString())
            ?->update($this->contract->forDatabaseUpdate());

        return $this->contract;
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
