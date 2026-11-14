<?php

declare(strict_types=1);

namespace App\InvoiceItem\Factories;

use App\Invoice\InvoiceEloquentModel;
use App\InvoiceItem\InvoiceItemEloquentModel;
use App\InvoiceItem\Properties\Description;
use App\InvoiceItem\Properties\InvoiceId;
use App\InvoiceItem\Properties\Position;
use App\InvoiceItem\Properties\Quantity;
use App\InvoiceItem\Properties\ServiceId;
use App\InvoiceItem\Properties\TeamId;
use App\InvoiceItem\Properties\UnitPriceInCents;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItemEloquentModel>
 */
final class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItemEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            InvoiceId::getDatabaseTableColumnName() => InvoiceEloquentModel::factory(),
            ServiceId::getDatabaseTableColumnName() => null,
            Position::getDatabaseTableColumnName() => 0,
            Description::getDatabaseTableColumnName() => fake()->sentence(3),
            Quantity::getDatabaseTableColumnName() => 1,
            UnitPriceInCents::getDatabaseTableColumnName() => fake()->numberBetween(1000, 50000),
        ];
    }
}
