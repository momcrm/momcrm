<?php

declare(strict_types=1);

namespace App\TeamInvitation;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use App\Team\TeamEloquentModel;
use App\TeamInvitation\Factories\TeamInvitationFactory;
use App\TeamInvitation\Properties\AcceptedAt;
use App\TeamInvitation\Properties\ExpiresAt;
use App\TeamInvitation\Properties\Id;
use App\TeamInvitation\Properties\InvitedById;
use App\TeamInvitation\Properties\TeamId;
use App\User\UserEloquentModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $created_at
 * @property string $email
 * @property CarbonImmutable|null $expires_at
 * @property string $id
 * @property string|null $invited_by_id
 * @property string $role
 * @property string $team_id
 * @property string $token_hash
 * @property CarbonImmutable|null $updated_at
 */
#[Hidden(['token_hash'])]
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class TeamInvitationEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::TeamInvitations->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    /**
     * @return BelongsTo<TeamEloquentModel, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(
            related: TeamEloquentModel::class,
            foreignKey: TeamId::getDatabaseTableColumnName(),
            relation: TeamInvitationRelationship::Team->value,
        );
    }

    /**
     * @return BelongsTo<UserEloquentModel, $this>
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(
            related: UserEloquentModel::class,
            foreignKey: InvitedById::getDatabaseTableColumnName(),
            relation: TeamInvitationRelationship::InvitedBy->value,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            AcceptedAt::getDatabaseTableColumnName() => 'immutable_datetime',
            ExpiresAt::getDatabaseTableColumnName() => 'immutable_datetime',
        ];
    }

    protected static function newFactory(): TeamInvitationFactory
    {
        return TeamInvitationFactory::new();
    }
}
