<?php

declare(strict_types=1);

namespace App\Customer\Actions;

use App\Customer\Customer;
use App\Customer\CustomerEloquentModel;
use App\Customer\Properties\Id;
use App\Shared\Actions\NextTeamNumber;
use App\Shared\Table;
use Illuminate\Support\Facades\DB;

final readonly class CreateCustomer
{
    public function __construct(
        private Customer $customer,
    ) {}

    public function __invoke(): Customer
    {
        return DB::transaction(function (): Customer {
            if ($this->customer->getId()->isNull()) {
                $this->customer->setId(Id::generate());
            }

            $this->customer->setNumber(NextTeamNumber::for(Table::Customers, $this->customer->getTeamId()->toString()));

            $model = CustomerEloquentModel::query()->create($this->customer->forDatabaseCreate());

            return Customer::fromEloquentModel($model->refresh());
        });
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
