<?php

declare(strict_types=1);

namespace App\SocialAccount\Factories;

use App\SocialAccount\Properties\Email;
use App\SocialAccount\Properties\Provider;
use App\SocialAccount\Properties\ProviderUserId;
use App\SocialAccount\Properties\UserId;
use App\SocialAccount\SocialAccountEloquentModel;
use App\SocialAccount\SocialProviderEnum;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccountEloquentModel>
 */
final class SocialAccountFactory extends Factory
{
    protected $model = SocialAccountEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            UserId::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            Provider::getDatabaseTableColumnName() => SocialProviderEnum::Google->value,
            ProviderUserId::getDatabaseTableColumnName() => (string) fake()->unique()->randomNumber(9),
            Email::getDatabaseTableColumnName() => fake()->safeEmail(),
        ];
    }
}
