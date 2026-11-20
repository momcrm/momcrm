<?php

declare(strict_types=1);

namespace App\PaymentAllocation\Factories;

use App\Invoice\InvoiceEloquentModel;
use App\Payment\PaymentEloquentModel;
use App\PaymentAllocation\PaymentAllocationEloquentModel;
use App\PaymentAllocation\Properties\AmountInCents;
use App\PaymentAllocation\Properties\InvoiceId;
use App\PaymentAllocation\Properties\PaymentId;
use App\PaymentAllocation\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAllocationEloquentModel>
 */
final class PaymentAllocationFactory extends Factory
{
    protected $model = PaymentAllocationEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            PaymentId::getDatabaseTableColumnName() => PaymentEloquentModel::factory(),
            InvoiceId::getDatabaseTableColumnName() => InvoiceEloquentModel::factory(),
            AmountInCents::getDatabaseTableColumnName() => fake()->numberBetween(100, 10000),
        ];
    }
}
