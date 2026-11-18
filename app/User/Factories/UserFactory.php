<?php

declare(strict_types=1);

namespace App\User\Factories;

use App\Team\Actions\CreatePersonalTeam;
use App\TeamUser\Actions\CreateTeamUser;
use App\TeamUser\TeamRoleEnum;
use App\User\Properties\Email;
use App\User\Properties\EmailVerifiedAt;
use App\User\Properties\IsPlatformAdmin;
use App\User\Properties\Name;
use App\User\Properties\OnboardedAt;
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
            OnboardedAt::getDatabaseTableColumnName() => now(),
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
     * Indicate that the user has not finished onboarding yet.
     */
    public function notOnboarded(): static
    {
        return $this->state(fn (array $attributes): array => [
            OnboardedAt::getDatabaseTableColumnName() => null,
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
     * Put the user in a team with the given role and make it their current team.
     */
    public function inTeam(string $teamId, TeamRoleEnum $role = TeamRoleEnum::Member): static
    {
        return $this
            ->state(['current_team_id' => $teamId])
            ->afterCreating(function (UserEloquentModel $user) use ($teamId, $role): void {
                CreateTeamUser::fromIds(teamId: $teamId, userId: $user->id, role: $role);
            });
    }

    /**
     * Indicate that the user looks after the installation, such as its `.env`.
     */
    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes): array => [
            IsPlatformAdmin::getDatabaseTableColumnName() => true,
        ]);
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
