<?php

declare(strict_types=1);

namespace App\Invoice\Factories;

use App\Customer\CustomerEloquentModel;
use App\Invoice\InvoiceEloquentModel;
use App\Invoice\InvoiceStatusEnum;
use App\Invoice\Properties\CustomerId;
use App\Invoice\Properties\DueOn;
use App\Invoice\Properties\IssuedOn;
use App\Invoice\Properties\Notes;
use App\Invoice\Properties\Number;
use App\Invoice\Properties\PaidAt;
use App\Invoice\Properties\PurchaseOrder;
use App\Invoice\Properties\SentAt;
use App\Invoice\Properties\Status;
use App\Invoice\Properties\TaxRateBasisPoints;
use App\Invoice\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceEloquentModel>
 */
final class InvoiceFactory extends Factory
{
    protected $model = InvoiceEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Number::getDatabaseTableColumnName() => fake()->unique()->numberBetween(1, 1000000),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            IssuedOn::getDatabaseTableColumnName() => now()->toDateString(),
            DueOn::getDatabaseTableColumnName() => now()->addDays(14)->toDateString(),
            Status::getDatabaseTableColumnName() => InvoiceStatusEnum::Draft->value,
            TaxRateBasisPoints::getDatabaseTableColumnName() => 1000,
            PurchaseOrder::getDatabaseTableColumnName() => null,
            Notes::getDatabaseTableColumnName() => null,
            SentAt::getDatabaseTableColumnName() => null,
            PaidAt::getDatabaseTableColumnName() => null,
        ];
    }
}
