<?php

declare(strict_types=1);

namespace App\Secret\Factories;

use App\Customer\CustomerEloquentModel;
use App\Secret\Properties\AssetId;
use App\Secret\Properties\CustomerId;
use App\Secret\Properties\Description;
use App\Secret\Properties\TeamId;
use App\Secret\Properties\Title;
use App\Secret\SecretEloquentModel;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SecretEloquentModel>
 */
final class SecretFactory extends Factory
{
    protected $model = SecretEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            CustomerId::getDatabaseTableColumnName() => CustomerEloquentModel::factory(),
            AssetId::getDatabaseTableColumnName() => null,
            Title::getDatabaseTableColumnName() => fake()->randomElement(['Firewall admin', 'Wi-Fi', 'Microsoft 365 admin', 'NAS']),
            Description::getDatabaseTableColumnName() => null,
            SecretEloquentModel::VALUES => [
                ['key' => 'username', 'value' => 'admin'],
                ['key' => 'password', 'value' => fake()->password(16)],
            ],
        ];
    }
}
