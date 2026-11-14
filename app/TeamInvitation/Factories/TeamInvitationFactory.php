<?php

declare(strict_types=1);

namespace App\TeamInvitation\Factories;

use App\Team\TeamEloquentModel;
use App\TeamInvitation\Properties\AcceptedAt;
use App\TeamInvitation\Properties\Email;
use App\TeamInvitation\Properties\ExpiresAt;
use App\TeamInvitation\Properties\InvitedById;
use App\TeamInvitation\Properties\Role;
use App\TeamInvitation\Properties\TeamId;
use App\TeamInvitation\Properties\TokenHash;
use App\TeamInvitation\TeamInvitationEloquentModel;
use App\TeamUser\TeamRoleEnum;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvitationEloquentModel>
 */
final class TeamInvitationFactory extends Factory
{
    protected $model = TeamInvitationEloquentModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TeamId::getDatabaseTableColumnName() => TeamEloquentModel::factory(),
            Email::getDatabaseTableColumnName() => fake()->unique()->safeEmail(),
            Role::getDatabaseTableColumnName() => TeamRoleEnum::Member->value,
            TokenHash::getDatabaseTableColumnName() => hash('sha256', Str::random(40)),
            InvitedById::getDatabaseTableColumnName() => UserEloquentModel::factory(),
            AcceptedAt::getDatabaseTableColumnName() => null,
            ExpiresAt::getDatabaseTableColumnName() => now()->addDays(7),
        ];
    }
}
