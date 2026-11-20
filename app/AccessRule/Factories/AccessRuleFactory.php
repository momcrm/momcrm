<?php

declare(strict_types=1);

namespace App\AccessRule\Factories;

use App\AccessRule\AccessAudienceEnum;
use App\AccessRule\AccessEffectEnum;
use App\AccessRule\AccessRuleEloquentModel;
use App\AccessRule\Properties\Actions;
use App\AccessRule\Properties\Audience;
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
            Audience::getDatabaseTableColumnName() => AccessAudienceEnum::Staff->value,
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

    /**
     * A rule about customer contacts signed in to the portal.
     */
    public function forContacts(): static
    {
        return $this->state([Audience::getDatabaseTableColumnName() => AccessAudienceEnum::Contact->value]);
    }

    public function disabled(): static
    {
        return $this->state([IsEnabled::getDatabaseTableColumnName() => false]);
    }
}
