<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

/**
 * Deletes a customer. Its contacts and other records go with it through the
 * database's cascading foreign keys.
 */
final readonly class DeleteCustomer
{
    public function __construct(
        private Customer $customer,
    ) {}

    public function __invoke(): void
    {
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
