<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Team\Scopes\CurrentTeamScope;

final readonly class UpdateCustomer
{
    public function __construct(
        private Customer $customer,
    ) {}

    public function __invoke(): Customer
    {
        CustomerEloquentModel::query()
            ->withoutGlobalScope(CurrentTeamScope::class)
            ->find($this->customer->getId()->toString())
            ?->update($this->customer->forDatabaseUpdate());

        return $this->customer;
    }

    public static function fromCustomer(Customer $customer): Customer
    {
        return new self($customer)->handle();
    }

    public function handle(): Customer
    {
        return $this->__invoke();
    }

    public function execute(): Customer
    {
        return $this->__invoke();
    }
}
