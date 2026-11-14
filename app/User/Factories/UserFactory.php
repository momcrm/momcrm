<?php

declare(strict_types=1);

namespace App\User\Factories;

use App\Team\Actions\CreatePersonalTeam;
use App\User\Properties\Email;
use App\User\Properties\EmailVerifiedAt;
use App\User\Properties\Name;
use App\User\Properties\Password;
use App\User\User;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserEloquentModel>
 */
final class UserFactory extends Factory
{
    protected $model = UserEloquentModel::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            Name::getDatabaseTableColumnName() => fake()->name(),
            Email::getDatabaseTableColumnName() => fake()->unique()->safeEmail(),
            EmailVerifiedAt::getDatabaseTableColumnName() => now(),
            Password::getDatabaseTableColumnName() => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            EmailVerifiedAt::getDatabaseTableColumnName() => null,
        ]);
    }

    /**
     * Indicate that the user has a personal team, which is their current team.
     */
    public function withPersonalTeam(): static
    {
        return $this->afterCreating(function (UserEloquentModel $user): void {
            CreatePersonalTeam::fromUser(User::fromEloquentModel($user));

            $user->refresh();
        });
    }

    /**
     * Indicate that the user has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
