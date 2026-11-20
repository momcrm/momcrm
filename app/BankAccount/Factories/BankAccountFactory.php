<?php

declare(strict_types=1);

namespace App\BankAccount\Factories;

use App\BankAccount\BankAccountEloquentModel;
use App\BankAccount\BankAccountKindEnum;
use App\BankAccount\Properties\Currency;
use App\BankAccount\Properties\Kind;
use App\BankAccount\Properties\Name;
use App\BankAccount\Properties\TeamId;
use App\Shared\CurrencyEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankAccountEloquentModel>
 */
final class BankAccountFactory extends Factory
{
    protected $model = BankAccountEloquentModel::class;

    /**
     * An Australian dollar account whose statements are CSV or OFX files.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => 'Business account '.fake()->unique()->numerify('####'),
            Kind::getDatabaseTableColumnName() => BankAccountKindEnum::Statement->value,
            Currency::getDatabaseTableColumnName() => CurrencyEnum::default()->value,
        ];
    }
}
