<?php

declare(strict_types=1);

namespace App\Contract\Actions;

use App\Attachment\Actions\DeleteAttachments;
use App\Contract\Contract;
use App\Contract\ContractEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\Scopes\CurrentTeamScope;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a contract with its documents and their stored copies.
 */
final readonly class DeleteContract
{
    public function __construct(
        private Contract $contract,
    ) {}

    public function __invoke(): void
    {
        DB::transaction(function (): void {
            DeleteAttachments::for(MorphMapKeyEnum::Contract, $this->contract->getId()->toString());

            ContractEloquentModel::query()
                ->withoutGlobalScope(CurrentTeamScope::class)
                ->find($this->contract->getId()->toString())
                ?->delete();
        });
    }

    public static function fromContract(Contract $contract): void
    {
        new self($contract)->handle();
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
