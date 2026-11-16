<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Attachment\Actions\DeleteAttachments;
use App\Contract\Actions\FindContract;
use App\Contract\Contract;
use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Shared\MorphMapKeyEnum;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Deletes a customer. Its contacts and other records go with it through the
 * database's cascading foreign keys; its files and its contracts' documents,
 * which no foreign key holds, are deleted first along with their stored copies.
 */
final readonly class DeleteCustomer
{
    public function __construct(
        private Customer $customer,
    ) {}

    public function __invoke(): void
    {
        $customerId = $this->customer->getId()->toString();

        DeleteAttachments::for(MorphMapKeyEnum::Customer, $customerId);

        FindContract::forCustomer($customerId, $this->customer->getTeamId()->toString())
            ->each(fn (Contract $contract) => DeleteAttachments::for(MorphMapKeyEnum::Contract, $contract->getId()->toString()));

        CustomerEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->customer->getId()->toString())
            ?->delete();
    }

    public static function fromCustomer(Customer $customer): void
    {
        new self($customer)->handle();
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
