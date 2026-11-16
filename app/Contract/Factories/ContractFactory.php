<?php

declare(strict_types=1);

namespace App\Contract\Factories;

use App\Contract\ContractEloquentModel;
use App\Contract\ContractStatusEnum;
use App\Contract\ContractTypeEnum;
use App\Contract\Properties\CustomerId;
use App\Contract\Properties\EndsOn;
use App\Contract\Properties\StartsOn;
use App\Contract\Properties\Status;
use App\Contract\Properties\TeamId;
use App\Contract\Properties\Title;
use App\Contract\Properties\Type;
use App\Customer\CustomerEloquentModel;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractEloquentModel>
 */
final class ContractFactory extends Factory
{
    protected $model = ContractEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            Title::getDatabaseTableColumnName() => fake()->randomElement(['Managed services', 'Support SLA', 'Mutual NDA']),
            Type::getDatabaseTableColumnName() => ContractTypeEnum::Msa->value,
            Status::getDatabaseTableColumnName() => ContractStatusEnum::Active->value,
            StartsOn::getDatabaseTableColumnName() => now()->subYear()->toDateString(),
            EndsOn::getDatabaseTableColumnName() => now()->addYear()->toDateString(),
        ];
    }
}
