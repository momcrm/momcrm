<?php

declare(strict_types=1);

namespace App\User;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Properties\OwnerId;
use App\Team\TeamEloquentModel;
use App\TeamUser\Properties\Role;
use App\TeamUser\Properties\TeamId;
use App\TeamUser\Properties\UserId;
use App\User\Factories\UserFactory;
use App\User\Properties\CurrentTeamId;
use App\User\Properties\EmailVerifiedAt;
use App\User\Properties\Id;
use App\User\Properties\Password;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string|null $current_team_id
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[ObservedBy(AuditObserver::class)]
final class UserEloquentModel extends Authenticatable implements Auditable, MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUuids;
    use Notifiable;
    use PasskeyAuthenticatable;
    use TwoFactorAuthenticatable;

    protected $guarded = [];

    public function auditTeamId(): ?string
    {
        return null;
    }

    public function getTable(): string
    {
        return Table::Users->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * Related tables reference this model as UserId's column, not the class-derived default.
     */
    public function getForeignKey(): string
    {
        return UserId::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsToMany<TeamEloquentModel, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(
            related: TeamEloquentModel::class,
            table: Table::TeamUser->value,
            foreignPivotKey: UserId::getDatabaseTableColumnName(),
            relatedPivotKey: TeamId::getDatabaseTableColumnName(),
            relation: UserRelationship::Teams->value,
        )->withPivot(Role::getDatabaseTableColumnName())->withTimestamps();
    }

    /**
     * @return HasMany<TeamEloquentModel, $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(
            related: TeamEloquentModel::class,
            foreignKey: OwnerId::getDatabaseTableColumnName(),
        );
    }

    /**
     * @return BelongsTo<TeamEloquentModel, $this>
     */
    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(
            related: TeamEloquentModel::class,
            foreignKey: CurrentTeamId::getDatabaseTableColumnName(),
            relation: UserRelationship::CurrentTeam->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            EmailVerifiedAt::getDatabaseTableColumnName() => 'datetime',
            Password::getDatabaseTableColumnName() => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
