<?php

declare(strict_types=1);

namespace App\Folder\Factories;

use App\Customer\CustomerEloquentModel;
use App\Folder\FolderEloquentModel;
use App\Folder\Properties\CustomerId;
use App\Folder\Properties\Name;
use App\Folder\Properties\ParentId;
use App\Folder\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FolderEloquentModel>
 */
final class FolderFactory extends Factory
{
    protected $model = FolderEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            ParentId::getDatabaseTableColumnName() => null,
            Name::getDatabaseTableColumnName() => fake()->randomElement(['Contracts', 'Quotes', 'Network diagrams', 'Photos']),
        ];
    }
}
