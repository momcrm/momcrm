<?php

declare(strict_types=1);

namespace App\AccessRule\Factories;

use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Properties\Actions;
use App\AccessRule\Properties\Conditions;
use App\AccessRule\Properties\Effect;
use App\AccessRule\Properties\IsEnabled;
use App\AccessRule\Properties\Name;
use App\AccessRule\Properties\Resources;
use App\AccessRule\Properties\TeamId;
use App\Team\TeamEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessRuleEloquentModel>
 */
final class AccessRuleFactory extends Factory
{
    protected $model = AccessRuleEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Name::getDatabaseTableColumnName() => fake()->sentence(3),
            Resources::getDatabaseTableColumnName() => [],
            Actions::getDatabaseTableColumnName() => [],
            Effect::getDatabaseTableColumnName() => AccessEffectEnum::Allow->value,
            Conditions::getDatabaseTableColumnName() => [],
            IsEnabled::getDatabaseTableColumnName() => true,
        ];
    }

    public function deny(): static
    {
        return $this->state([Effect::getDatabaseTableColumnName() => AccessEffectEnum::Deny->value]);
    }

    public function disabled(): static
    {
        return $this->state([IsEnabled::getDatabaseTableColumnName() => false]);
    }
}
