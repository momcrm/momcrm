<?php

declare(strict_types=1);

namespace App\AccessRule;

use App\AccessRule\Factories\AccessRuleFactory;
use App\AccessRule\Properties\Actions;
use App\AccessRule\Properties\Conditions;
use App\AccessRule\Properties\Id;
use App\AccessRule\Properties\IsEnabled;
use App\AccessRule\Properties\Resources;
use App\AuditLog\AuditObserver;
use App\AuditLog\Contracts\Auditable;
use App\Shared\Table;
use App\Team\Scopes\CurrentTeamScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array<int, string> $actions
 * @property array<int, array<string, mixed>> $conditions
 * @property CarbonImmutable|null $created_at
 * @property string|null $created_by_id
 * @property string $effect
 * @property string $id
 * @property bool $is_enabled
 * @property string $name
 * @property array<int, string> $resources
 * @property string $team_id
 * @property CarbonImmutable|null $updated_at
 */
#[ObservedBy(AuditObserver::class)]
#[ScopedBy(CurrentTeamScope::class)]
final class AccessRuleEloquentModel extends Model implements Auditable
{
    /** @use HasFactory<AccessRuleFactory> */
    use HasFactory;

    use HasUuids;

    protected $guarded = [];

    public function auditTeamId(): string
    {
        return $this->team_id;
    }

    public function getTable(): string
    {
        return Table::AccessRules->value;
    }

    public function getKeyName(): string
    {
        return Id::getDatabaseTableColumnName();
    }

    protected static function newFactory(): AccessRuleFactory
    {
        return AccessRuleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            Actions::getDatabaseTableColumnName() => 'array',
            Conditions::getDatabaseTableColumnName() => 'array',
            IsEnabled::getDatabaseTableColumnName() => 'boolean',
            Resources::getDatabaseTableColumnName() => 'array',
        ];
    }
}
