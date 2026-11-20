<?php

declare(strict_types=1);

namespace App\Payment\Factories;

use App\Customer\CustomerEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\Payment\PaymentMethodEnum;
use App\Payment\Properties\AmountInCents;
use App\Payment\Properties\Currency;
use App\Payment\Properties\CustomerId;
use App\Payment\Properties\Method;
use App\Payment\Properties\Notes;
use App\Payment\Properties\Number;
use App\Payment\Properties\PaidOn;
use App\Payment\Properties\Reference;
use App\Payment\Properties\TeamId;
use App\Shared\CurrencyEnum;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentEloquentModel>
 */
final class PaymentFactory extends Factory
{
    protected $model = PaymentEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            PaidOn::getDatabaseTableColumnName() => now()->toDateString(),
            AmountInCents::getDatabaseTableColumnName() => fake()->numberBetween(1000, 100000),
            Currency::getDatabaseTableColumnName() => CurrencyEnum::default()->value,
            Method::getDatabaseTableColumnName() => PaymentMethodEnum::BankTransfer->value,
            Reference::getDatabaseTableColumnName() => null,
            Notes::getDatabaseTableColumnName() => null,
        ];
    }
}
