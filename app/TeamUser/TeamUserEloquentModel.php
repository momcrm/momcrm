<?php

declare(strict_types=1);

namespace App\TeamUser;

use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\TeamUser\Properties\Id;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $team_id
 * @property string $user_id
 * @property string $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
final class TeamUserEloquentModel extends Model implements Auditable
{
    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::TeamUser->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }
}
