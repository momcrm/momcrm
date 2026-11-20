<?php

declare(strict_types=1);

namespace App\BankRule\Factories;

use App\BankRule\BankRuleEloquentModel;
use App\BankRule\BankRuleKindEnum;
use App\BankRule\Properties\Contains;
use App\BankRule\Properties\Kind;
use App\BankRule\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankRuleEloquentModel>
 */
final class BankRuleFactory extends Factory
{
    protected $model = BankRuleEloquentModel::class;

    /**
     * A hide rule; give a match rule its customer_id and kind.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Kind::getDatabaseTableColumnName() => BankRuleKindEnum::Hide->value,
            Contains::getDatabaseTableColumnName() => 'TRANSFER '.fake()->unique()->numerify('######'),
        ];
    }
}
