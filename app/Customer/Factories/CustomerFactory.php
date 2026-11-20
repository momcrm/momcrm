<?php

declare(strict_types=1);

namespace App\Customer\Factories;

use App\Customer\CustomerBillingTypeEnum;
use App\Customer\CustomerEloquentModel;
use App\Customer\CustomerStatusEnum;
use App\Customer\Properties\BillingType;
use App\Customer\Properties\Email;
use App\Customer\Properties\IsSample;
use App\Customer\Properties\Name;
use App\Customer\Properties\Notes;
use App\Customer\Properties\Number;
use App\Customer\Properties\Phone;
use App\Customer\Properties\Prefix;
use App\Customer\Properties\Status;
use App\Customer\Properties\TaxId;
use App\Customer\Properties\TaxIdLabel;
use App\Customer\Properties\TeamId;
use App\Customer\Properties\Website;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerEloquentModel>
 */
final class CustomerFactory extends Factory
{
    protected $model = CustomerEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            Name::getDatabaseTableColumnName() => fake()->company(),
            Prefix::getDatabaseTableColumnName() => null,
            TaxId::getDatabaseTableColumnName() => null,
            TaxIdLabel::getDatabaseTableColumnName() => null,
            Email::getDatabaseTableColumnName() => fake()->companyEmail(),
            Phone::getDatabaseTableColumnName() => fake()->phoneNumber(),
            Website::getDatabaseTableColumnName() => null,
            Status::getDatabaseTableColumnName() => CustomerStatusEnum::Active->value,
            BillingType::getDatabaseTableColumnName() => CustomerBillingTypeEnum::Manual->value,
            Notes::getDatabaseTableColumnName() => null,
            IsSample::getDatabaseTableColumnName() => false,
        ];
    }
}
